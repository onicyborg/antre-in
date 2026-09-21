<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Exceptions\DraftConflictException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleStatusHistory;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(private SaleCalculator $calculator, private NumberGenerator $numbers, private StockService $stock, private ActivityLogger $logger) {}

    public function saveDraft(User $user, array $payload): Sale
    {
        return DB::transaction(function () use ($user, $payload): Sale {
            $this->ensureDraftCapacity();
            $priced = $this->priceItems($payload['items']);
            $totals = $this->calculate($priced, $payload);
            $sale = Sale::create(array_merge($totals, ['status'=>SaleStatus::Draft,'draft_number'=>$this->numbers->draft(),'label'=>$payload['label'] ?? null,'user_id'=>$user->id,'drafted_at'=>now(),'note'=>$payload['note'] ?? null]));
            $this->replaceItems($sale, $priced);
            $this->recordStatus($sale, null, SaleStatus::Draft, $user, 'Draft dibuat.');
            $this->logger->log($user,'draft_saved','sales',$sale->id,null,$sale->fresh()->toArray());
            return $sale->load('items');
        });
    }

    public function updateDraft(User $user, Sale $draft, array $payload): Sale
    {
        return DB::transaction(function () use ($user, $draft, $payload): Sale {
            $draft = Sale::query()->whereKey($draft->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($draft); $this->assertLockAvailable($draft, $user);
            $priced = $this->priceItems($payload['items']);
            $draft->update(array_merge($this->calculate($priced, $payload), ['label'=>$payload['label'] ?? $draft->label,'discount_type'=>$payload['discount_type'] ?? null,'discount_value'=>(int) ($payload['discount_value'] ?? 0),'note'=>$payload['note'] ?? null,'locked_by'=>null,'locked_at'=>null]));
            $draft->items()->delete(); $this->replaceItems($draft, $priced);
            return $draft->fresh('items');
        });
    }

    public function resumeDraft(User $user, Sale $draft): array
    {
        return DB::transaction(function () use ($user, $draft): array {
            $draft = Sale::query()->whereKey($draft->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($draft); $this->assertLockAvailable($draft, $user);
            $ids = $draft->items()->orderBy('product_id')->pluck('product_id')->all();
            $products = Product::query()->with('unit')->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $warnings = []; $items = [];
            foreach ($draft->items as $old) {
                $product = $products->get($old->product_id);
                if (!$product || !$product->is_active || (int) $product->stock < 1) { $warnings[] = "Produk {$old->product_name} tidak tersedia dan dikeluarkan dari draft."; continue; }
                $quantity = (int) $old->quantity;
                if ((int) $product->sell_price !== (int) $old->unit_price) $warnings[] = 'Harga '.$product->name.' berubah dari '.format_rupiah((int) $old->unit_price).' menjadi '.format_rupiah((int) $product->sell_price).'.';
                if ((int) $product->stock < $quantity) { $warnings[] = "Stok {$product->name} hanya {$product->stock}, jumlah disesuaikan."; $quantity = (int) $product->stock; }
                $items[] = ['product_id'=>$product->id,'name'=>$product->name,'sku'=>$product->sku,'unit'=>$product->unit->name,'quantity'=>$quantity,'unit_price'=>(int) $product->sell_price,'stock'=>(int) $product->stock];
            }
            $draft->update(['locked_by'=>$user->id,'locked_at'=>now()]);
            $this->logger->log($user,'draft_resumed','sales',$draft->id,null,['locked_by'=>$user->id,'locked_at'=>$draft->locked_at]);
            return ['draft'=>$draft->fresh(), 'items'=>$items, 'warnings'=>$warnings];
        });
    }

    public function releaseDraft(User $user, Sale $draft): Sale
    {
        return DB::transaction(function () use ($user, $draft): Sale {
            $draft=Sale::query()->whereKey($draft->id)->lockForUpdate()->firstOrFail(); $this->assertDraft($draft);
            if ($draft->locked_by && $draft->locked_by !== $user->id && $this->lockIsActive($draft)) throw new DraftConflictException('Draft sedang dibuka oleh '.optional($draft->lockedBy)->name.'.');
            $draft->update(['locked_by'=>null,'locked_at'=>null]); return $draft->fresh();
        });
    }

    public function discardDraft(User $user, Sale $draft): Sale
    {
        return DB::transaction(function () use ($user, $draft): Sale {
            $draft=Sale::query()->whereKey($draft->id)->lockForUpdate()->firstOrFail(); $this->assertDraft($draft);
            if (!$user->isAdmin() && $draft->user_id !== $user->id) throw ValidationException::withMessages(['draft'=>'Draft hanya dapat dibuang oleh pembuatnya.']);
            if ($draft->locked_by && $draft->locked_by !== $user->id && $this->lockIsActive($draft)) throw new DraftConflictException('Draft sedang dibuka oleh '.optional($draft->lockedBy)->name.'.');
            $old=$draft->toArray(); $draft->update(['status'=>SaleStatus::Discarded,'discard_reason'=>'manual','discarded_at'=>now(),'locked_by'=>null,'locked_at'=>null]); $this->recordStatus($draft,SaleStatus::Draft,SaleStatus::Discarded,$user,'Draft dibuang.'); $this->logger->log($user,'draft_discarded','sales',$draft->id,$old,$draft->fresh()->toArray()); return $draft->fresh();
        });
    }

    public function checkout(User $user, array $payload): Sale
    {
        return DB::transaction(function () use ($user, $payload): Sale {
            $draft = null;
            if (!empty($payload['draft_id'])) { $draft=Sale::query()->whereKey($payload['draft_id'])->lockForUpdate()->firstOrFail(); $this->assertDraft($draft); $this->assertLockAvailable($draft, $user); }
            $priced=$this->priceItems($payload['items'], true); $setting=StoreSetting::current(); $method=PaymentMethod::from($payload['payment_method']); $paid=(int)($payload['paid_amount']??0); $totals=$this->calculate($priced,$payload,$setting,$paid,$method);
            if ($method===PaymentMethod::Cash && $paid < $totals['total']) throw ValidationException::withMessages(['paid_amount'=>'Jumlah bayar tunai kurang dari total.']);
            if ($method!==PaymentMethod::Cash && $paid !== $totals['total']) throw ValidationException::withMessages(['paid_amount'=>'Jumlah bayar harus sama dengan total.']);
            $now=now(); $sale=$draft ?: new Sale();
            $sale->fill(array_merge($totals,['status'=>SaleStatus::Completed,'invoice_number'=>$draft?->invoice_number ?: $this->numbers->invoice($now),'user_id'=>$draft?->user_id ?: $user->id,'completed_by'=>$user->id,'discount_type'=>$payload['discount_type']??null,'discount_value'=>(int)($payload['discount_value']??0),'tax_percent'=>$setting->tax_percent,'payment_method'=>$method,'paid_amount'=>$paid,'payment_reference'=>$payload['payment_reference']??null,'note'=>$payload['note']??null,'completed_at'=>$now,'locked_by'=>null,'locked_at'=>null]));
            $sale->save(); $sale->items()->delete(); $this->replaceItems($sale,$priced);
            $this->recordStatus($sale, $draft ? SaleStatus::Draft : null, SaleStatus::Completed, $user, 'Transaksi selesai.');
            $this->logger->log($user,'checkout','sales',$sale->id,$draft?->toArray(),$sale->fresh()->toArray());
            foreach($priced as $item) $this->stock->decreaseForSale($item['product'],$user,(int)$item['quantity'],$sale->id,'Penjualan '.$sale->invoice_number);
            return $sale->fresh('items');
        });
    }

    public function pruneExpired(): int
    {
        $setting=StoreSetting::current(); $count=0;
        Sale::query()->where('status',SaleStatus::Draft)->where('drafted_at','<',now()->subHours($setting->draft_expire_hours))->where(function($query): void { $query->whereNull('locked_at')->orWhere('locked_at','<',now()->subMinutes(config('pos.draft_lock_minutes'))); })->chunkById(100,function($drafts) use (&$count): void { foreach($drafts as $draft){$draft->update(['status'=>SaleStatus::Discarded,'discard_reason'=>'expired','discarded_at'=>now(),'locked_by'=>null,'locked_at'=>null]);$this->recordStatus($draft,SaleStatus::Draft,SaleStatus::Discarded,null,'Draft kedaluwarsa.');$this->logger->log(null,'draft_discarded','sales',$draft->id,null,$draft->fresh()->toArray());$count++;} });
        Sale::query()->where('status',SaleStatus::Draft)->whereNotNull('locked_at')->where('locked_at','<',now()->subMinutes(config('pos.draft_lock_minutes')))->update(['locked_by'=>null,'locked_at'=>null]); return $count;
    }

    private function ensureDraftCapacity(): void { $limit=(int)StoreSetting::current()->max_active_drafts; if(Sale::query()->where('status',SaleStatus::Draft)->count()>=$limit) throw ValidationException::withMessages(['draft'=>'Batas draft aktif telah tercapai.']); }
    private function assertDraft(Sale $sale): void { if($sale->status!==SaleStatus::Draft) throw ValidationException::withMessages(['draft'=>'Transaksi tersebut bukan draft aktif.']); }
    private function lockIsActive(Sale $sale): bool { return $sale->locked_at && $sale->locked_at->gt(now()->subMinutes(config('pos.draft_lock_minutes'))); }
    private function assertLockAvailable(Sale $sale, User $user): void { if($sale->locked_by && $sale->locked_by!==$user->id && $this->lockIsActive($sale)) throw new DraftConflictException('Draft sedang dibuka oleh '.optional($sale->lockedBy)->name.'.'); if($sale->locked_by && !$this->lockIsActive($sale)) $sale->update(['locked_by'=>null,'locked_at'=>null]); }
    private function priceItems(array $items, bool $checkStock=false): array
    {
        $ids=collect($items)->pluck('product_id')->sort()->values()->all(); $products=Product::query()->with('unit')->whereIn('id',$ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id'); $errors=[]; $priced=[];
        foreach($items as $index=>$item){$p=$products->get($item['product_id']);if(!$p||!$p->is_active)$errors["items.$index.quantity"][]='Produk tidak aktif atau tidak ditemukan.';elseif($checkStock&&$p->stock<(int)$item['quantity'])$errors["items.$index.quantity"][]="Stok {$p->name} tidak mencukupi (tersedia {$p->stock}).";else$priced[]=['product'=>$p,'quantity'=>(int)$item['quantity'],'unit_price'=>(int)$p->sell_price];} if($errors)throw ValidationException::withMessages($errors); return $priced;
    }
    private function calculate(array $priced, array $payload, ?StoreSetting $setting=null, int $paid=0, PaymentMethod $method=PaymentMethod::Cash): array { $setting??=StoreSetting::current();$items=array_map(static fn(array $x):array=>['quantity'=>$x['quantity'],'unit_price'=>$x['unit_price']],$priced);return $this->calculator->calculate($items,$payload['discount_type']??null,(int)($payload['discount_value']??0),(float)$setting->tax_percent,$paid,$method); }
    private function replaceItems(Sale $sale,array $priced):void{foreach($priced as $item){$p=$item['product'];$sale->items()->create(['product_id'=>$p->id,'product_name'=>$p->name,'sku'=>$p->sku,'unit_name'=>$p->unit->name,'quantity'=>$item['quantity'],'unit_price'=>$p->sell_price,'cost_price'=>$p->cost_price,'subtotal'=>$item['quantity']*(int)$p->sell_price]);}}
    private function recordStatus(Sale $sale, ?SaleStatus $from, SaleStatus $to, ?User $user, ?string $note=null): void { SaleStatusHistory::create(['sale_id'=>$sale->id,'from_status'=>$from?->value,'to_status'=>$to->value,'user_id'=>$user?->id,'note'=>$note]); }

    public function void(User $user, Sale $sale, string $reason): Sale
    {
        if (!$user->isAdmin()) abort(403, 'Hanya admin yang dapat membatalkan transaksi.');
        if (trim($reason) === '') throw ValidationException::withMessages(['void_reason'=>'Alasan void wajib diisi.']);
        return DB::transaction(function () use ($user, $sale, $reason): Sale {
            $sale=Sale::query()->whereKey($sale->id)->lockForUpdate()->with('items')->firstOrFail();
            if ($sale->status !== SaleStatus::Completed) throw ValidationException::withMessages(['sale'=>'Transaksi hanya dapat di-void satu kali dan harus berstatus selesai.']);
            foreach($sale->items as $item){$product=Product::query()->findOrFail($item->product_id);$this->stock->increaseForVoid($product,$user,(int)$item->quantity,$sale->id,'Void '.$sale->invoice_number);}
            $sale->update(['status'=>SaleStatus::Voided,'voided_at'=>now(),'voided_by'=>$user->id,'void_reason'=>$reason]);
            $this->recordStatus($sale,SaleStatus::Completed,SaleStatus::Voided,$user,$reason);
            $this->logger->log($user,'void','sales',$sale->id,['status'=>SaleStatus::Completed->value],$sale->fresh()->toArray());
            return $sale->fresh('items');
        });
    }
}
