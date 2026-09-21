<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Closure;
use InvalidArgumentException;

class StockService
{
    public function __construct(private readonly ActivityLogger $logger) {}
    public function recordMovement(
        Product $product,
        User $user,
        StockMovementType $type,
        int $quantityChange,
        ?string $note = null,
        ?string $saleId = null,
    ): StockMovement {
        return $this->mutate($product, $user, $type, fn (): int => $quantityChange, $note, $saleId);
    }

    public function receive(Product $product, User $user, int $quantity, ?string $note = null, ?int $costPrice = null): StockMovement
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Jumlah stok masuk harus lebih besar dari nol.');
        }

        return $this->mutate($product, $user, StockMovementType::In, fn (): int => $quantity, $note, null, $costPrice);
    }

    public function adjustTo(Product $product, User $user, int $actualStock, string $note): StockMovement
    {
        if ($actualStock < 0) {
            throw new InvalidArgumentException('Stok aktual tidak boleh kurang dari nol.');
        }

        if (trim($note) === '') {
            throw new InvalidArgumentException('Catatan penyesuaian wajib diisi.');
        }

        return $this->mutate($product, $user, StockMovementType::Adjustment, fn (Product $lockedProduct): int => $actualStock - (int) $lockedProduct->stock, $note);
    }

    public function decreaseForSale(Product $product, User $user, int $quantity, ?string $saleId = null, ?string $note = null): StockMovement
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Jumlah pengurangan stok harus lebih besar dari nol.');
        }

        return $this->mutate($product, $user, StockMovementType::Sale, fn (): int => -$quantity, $note, $saleId);
    }

    public function increaseForVoid(Product $product, User $user, int $quantity, ?string $saleId = null, ?string $note = null): StockMovement
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Jumlah pengembalian stok harus lebih besar dari nol.');
        }

        return $this->mutate($product, $user, StockMovementType::VoidReturn, fn (): int => $quantity, $note, $saleId);
    }

    private function mutate(
        Product $product,
        User $user,
        StockMovementType $type,
        Closure $quantityResolver,
        ?string $note = null,
        ?string $saleId = null,
        ?int $costPrice = null,
    ): StockMovement {
        return DB::transaction(function () use ($product, $user, $type, $quantityResolver, $note, $saleId, $costPrice): StockMovement {
            $lockedProduct = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();
            $before = (int) $lockedProduct->stock;
            $quantityChange = (int) $quantityResolver($lockedProduct);
            $after = $before + $quantityChange;

            if ($after < 0) {
                throw new \DomainException('Stok produk tidak boleh kurang dari nol.');
            }

            $lockedProduct->forceFill(['stock' => $after]);
            if ($costPrice !== null) {
                $lockedProduct->forceFill(['cost_price' => $costPrice]);
            }
            $lockedProduct->save();

            $movement=StockMovement::create([
                'product_id' => $lockedProduct->getKey(),
                'user_id' => $user->getKey(),
                'type' => $type,
                'quantity_change' => $quantityChange,
                'stock_before' => $before,
                'stock_after' => $after,
                'sale_id' => $saleId,
                'note' => $note,
            ]);
            if ($type === StockMovementType::In) $this->logger->log($user,'stock_received','products',$lockedProduct->getKey(),['stock'=>$before],['stock'=>$after,'quantity_change'=>$quantityChange,'movement_id'=>$movement->id]);
            if ($type === StockMovementType::Adjustment) $this->logger->log($user,'stock_adjusted','products',$lockedProduct->getKey(),['stock'=>$before],['stock'=>$after,'quantity_change'=>$quantityChange,'movement_id'=>$movement->id]);
            return $movement;
        });
    }
}
