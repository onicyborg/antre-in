<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\User;
use App\Models\Sale;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->product = Product::factory()->create(['name' => 'Produk Stok Uji']);
    }

    public function test_admin_can_receive_stock_and_write_movement(): void
    {
        $response = $this->actingAs($this->admin)->post(route('stock.receive'), [
            'product_id' => $this->product->id,
            'quantity' => 10,
            'cost_price' => 12500,
            'note' => 'Restok pemasok',
        ]);

        $response->assertRedirect(route('stock.index'));
        $this->product->refresh();
        $this->assertSame(10, $this->product->stock);
        $this->assertSame(12500, $this->product->cost_price);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'user_id' => $this->admin->id,
            'type' => StockMovementType::In->value,
            'quantity_change' => 10,
            'stock_before' => 0,
            'stock_after' => 10,
            'note' => 'Restok pemasok',
        ]);
    }

    public function test_adjustment_sets_actual_stock_and_records_difference(): void
    {
        $service = app(StockService::class);
        $service->receive($this->product, $this->admin, 10, 'Stok awal');

        $response = $this->actingAs($this->admin)->post(route('stock.adjust'), [
            'product_id' => $this->product->id,
            'actual_stock' => 7,
            'note' => 'Hasil opname',
        ]);

        $response->assertRedirect(route('stock.index'));
        $this->product->refresh();
        $this->assertSame(7, $this->product->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => StockMovementType::Adjustment->value,
            'quantity_change' => -3,
            'stock_before' => 10,
            'stock_after' => 7,
            'note' => 'Hasil opname',
        ]);
    }

    public function test_adjustment_requires_note(): void
    {
        $this->from(route('stock.index'))->actingAs($this->admin)->post(route('stock.adjust'), [
            'product_id' => $this->product->id,
            'actual_stock' => 2,
        ])->assertRedirect(route('stock.index'))->assertSessionHasErrors('note');

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_stock_service_rejects_negative_result_without_side_effect(): void
    {
        $service = app(StockService::class);

        $this->expectException(\DomainException::class);
        try {
            $service->decreaseForSale($this->product, $this->admin, 1, 'sale-'.fake()->uuid());
        } finally {
            $this->assertDatabaseCount('stock_movements', 0);
            $this->assertSame(0, $this->product->fresh()->stock);
        }
    }

    public function test_sale_decrease_and_void_increase_keep_sale_reference(): void
    {
        $service = app(StockService::class);
        $service->receive($this->product, $this->admin, 5, 'Stok awal');
        $saleId = Sale::factory()->create(['user_id' => $this->admin->id])->id;

        $service->decreaseForSale($this->product, $this->admin, 2, $saleId, 'Penjualan');
        $service->increaseForVoid($this->product, $this->admin, 2, $saleId, 'Void transaksi');

        $this->assertSame(5, $this->product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['type' => StockMovementType::Sale->value, 'sale_id' => $saleId, 'quantity_change' => -2]);
        $this->assertDatabaseHas('stock_movements', ['type' => StockMovementType::VoidReturn->value, 'sale_id' => $saleId, 'quantity_change' => 2]);
    }

    public function test_kasir_cannot_access_stock_module(): void
    {
        $kasir = User::factory()->kasir()->create();

        $this->actingAs($kasir)->get(route('stock.index'))->assertForbidden();
        $this->actingAs($kasir)->get(route('stock.movements'))->assertForbidden();
    }
}
