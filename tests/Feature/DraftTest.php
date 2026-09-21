<?php

namespace Tests\Feature;

use App\Enums\SaleStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreSetting;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DraftTest extends TestCase
{
    use RefreshDatabase;

    private function context(int $stock = 10): array
    {
        $one=User::factory()->kasir()->create(); $two=User::factory()->kasir()->create(); $category=Category::factory()->create(); $unit=Unit::factory()->create(); $product=Product::factory()->create(['category_id'=>$category->id,'unit_id'=>$unit->id,'stock'=>$stock,'sell_price'=>15000]); return [$one,$two,$product];
    }

    private function payload(Product $product, int $quantity = 1): array { return ['items'=>[['product_id'=>$product->id,'quantity'=>$quantity]],'discount_type'=>'nominal','discount_value'=>0]; }

    public function test_save_draft_keeps_stock_unchanged_and_lists_count(): void
    {
        [$kasir,, $product]=$this->context(7); $response=$this->actingAs($kasir)->postJson(route('pos.drafts.store'),$this->payload($product,3)+['label'=>'Ibu merah']);
        $response->assertCreated()->assertJsonPath('data.draft_number','DRF-'.now()->format('Ymd').'-001')->assertJsonPath('active_drafts',1); $this->assertSame(7,$product->fresh()->stock); $this->assertDatabaseHas('sales',['status'=>'draft','label'=>'Ibu merah']); $this->actingAs($kasir)->getJson(route('pos.drafts.index',['count_only'=>1]))->assertJson(['count'=>1]);
    }

    public function test_max_active_drafts_is_enforced(): void
    {
        [$kasir,, $product]=$this->context(); StoreSetting::current()->update(['max_active_drafts'=>1]); $this->actingAs($kasir)->postJson(route('pos.drafts.store'),$this->payload($product))->assertCreated(); $this->actingAs($kasir)->postJson(route('pos.drafts.store'),$this->payload($product))->assertStatus(422)->assertJsonValidationErrors('draft');
    }

    public function test_resume_warns_for_price_and_stock_changes_and_locks(): void
    {
        [$one,$two,$product]=$this->context(5); $draft=$this->actingAs($one)->postJson(route('pos.drafts.store'),$this->payload($product,4))->json('data.id'); $product->forceFill(['sell_price'=>18000,'stock'=>2])->save(); $response=$this->actingAs($two)->postJson(route('pos.drafts.resume',$draft)); $response->assertOk()->assertJsonPath('data.items.0.quantity',2)->assertJsonCount(2,'warnings'); $this->assertDatabaseHas('sales',['id'=>$draft,'locked_by'=>$two->id]);
    }

    public function test_active_lock_returns_409_and_stale_lock_can_be_taken_over(): void
    {
        [$one,$two,$product]=$this->context(); $draft=Sale::factory()->create(['user_id'=>$one->id,'status'=>SaleStatus::Draft,'draft_number'=>'DRF-X','drafted_at'=>now(),'locked_by'=>$one->id,'locked_at'=>now()]); $draft->items()->create(['product_id'=>$product->id,'product_name'=>$product->name,'sku'=>$product->sku,'unit_name'=>'pcs','quantity'=>1,'unit_price'=>$product->sell_price,'cost_price'=>$product->cost_price,'subtotal'=>$product->sell_price]); $this->actingAs($two)->postJson(route('pos.drafts.resume',$draft))->assertStatus(409); $draft->update(['locked_at'=>now()->subMinutes(config('pos.draft_lock_minutes')+1)]); $this->actingAs($two)->postJson(route('pos.drafts.resume',$draft))->assertOk(); $this->assertDatabaseHas('sales',['id'=>$draft->id,'locked_by'=>$two->id]);
    }

    public function test_checkout_draft_converts_same_row_and_reduces_stock(): void
    {
        [$one,$two,$product]=$this->context(5); $draft=$this->actingAs($one)->postJson(route('pos.drafts.store'),$this->payload($product,2))->json('data.id'); $this->actingAs($two)->postJson(route('pos.drafts.resume',$draft))->assertOk(); $response=$this->actingAs($two)->postJson(route('pos.checkout'),$this->payload($product,2)+['draft_id'=>$draft,'payment_method'=>'cash','paid_amount'=>30000]); $response->assertCreated(); $sale=Sale::find($draft); $this->assertSame(SaleStatus::Completed,$sale->status); $this->assertNotNull($sale->invoice_number); $this->assertNotNull($sale->draft_number); $this->assertNotNull($sale->drafted_at); $this->assertSame(3,$product->fresh()->stock); $this->assertDatabaseCount('sales',1);
    }

    public function test_update_releases_lock_discard_permission_and_release(): void
    {
        [$one,$two,$product]=$this->context(); $draft=$this->actingAs($one)->postJson(route('pos.drafts.store'),$this->payload($product))->json('data.id'); $this->actingAs($two)->deleteJson(route('pos.drafts.destroy',$draft))->assertStatus(422); $this->actingAs($one)->postJson(route('pos.drafts.resume',$draft))->assertOk(); $this->actingAs($one)->putJson(route('pos.drafts.update',$draft),$this->payload($product,2)+['label'=>'Diperbarui'])->assertOk(); $this->assertDatabaseHas('sales',['id'=>$draft,'label'=>'Diperbarui','locked_by'=>null]); $this->actingAs($one)->deleteJson(route('pos.drafts.destroy',$draft))->assertOk(); $this->assertDatabaseHas('sales',['id'=>$draft,'status'=>'discarded','discard_reason'=>'manual']);
    }

    public function test_prune_discards_expired_draft_but_keeps_stock_unchanged(): void
    {
        [$kasir,, $product]=$this->context(8); $draft=$this->actingAs($kasir)->postJson(route('pos.drafts.store'),$this->payload($product,3))->json('data.id'); Sale::find($draft)->update(['drafted_at'=>now()->subHours(25)]); StoreSetting::current()->update(['draft_expire_hours'=>24]); $this->artisan('drafts:prune')->assertSuccessful(); $this->assertDatabaseHas('sales',['id'=>$draft,'status'=>'discarded','discard_reason'=>'expired']); $this->assertSame(8,$product->fresh()->stock);
    }

    public function test_only_one_checkout_can_consume_the_last_stock(): void
    {
        [$one,$two,$product]=$this->context(1); $first=$this->actingAs($one)->postJson(route('pos.drafts.store'),$this->payload($product))->json('data.id'); $second=$this->actingAs($two)->postJson(route('pos.drafts.store'),$this->payload($product))->json('data.id'); $this->actingAs($one)->postJson(route('pos.checkout'),$this->payload($product)+['draft_id'=>$first,'payment_method'=>'cash','paid_amount'=>15000])->assertCreated(); $this->actingAs($two)->postJson(route('pos.checkout'),$this->payload($product)+['draft_id'=>$second,'payment_method'=>'cash','paid_amount'=>15000])->assertStatus(422)->assertJsonValidationErrors('items.0.quantity'); $this->assertSame(0,$product->fresh()->stock);
    }
}
