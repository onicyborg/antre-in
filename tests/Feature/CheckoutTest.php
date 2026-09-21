<?php

namespace Tests\Feature;

use App\Enums\SaleStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function setupProduct(int $stock = 10, bool $active = true): array
    {
        $user = User::factory()->kasir()->create(); $category = Category::factory()->create(); $unit = Unit::factory()->create();
        $product = Product::factory()->create(['category_id'=>$category->id,'unit_id'=>$unit->id,'cost_price'=>5000,'sell_price'=>15000,'stock'=>$stock,'is_active'=>$active]);
        return [$user, $product];
    }

    public function test_checkout_uses_database_price_and_writes_sale_movement(): void
    {
        [$user,$product] = $this->setupProduct();
        $response = $this->actingAs($user)->postJson(route('pos.checkout'), ['items'=>[['product_id'=>$product->id,'quantity'=>2,'unit_price'=>1]],'payment_method'=>'cash','paid_amount'=>30000]);
        $response->assertCreated()->assertJsonPath('data.total', 30000);
        $this->assertDatabaseHas('sales', ['status'=>SaleStatus::Completed->value,'total'=>30000]);
        $this->assertDatabaseHas('stock_movements', ['product_id'=>$product->id,'type'=>'sale','quantity_change'=>-2]);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_cashier_product_endpoint_returns_active_products_and_supports_search(): void
    {
        [$user, $product] = $this->setupProduct();
        Product::factory()->create(['name' => 'Produk lain', 'is_active' => true]);
        Product::factory()->create(['name' => 'Produk nonaktif', 'is_active' => false]);

        $this->actingAs($user)->getJson(route('pos.products', ['q' => $product->sku]))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonPath('data.0.price', 15000);
    }

    public function test_insufficient_stock_returns_item_error_and_does_not_create_sale(): void
    {
        [$user,$product] = $this->setupProduct(1);
        $this->actingAs($user)->postJson(route('pos.checkout'), ['items'=>[['product_id'=>$product->id,'quantity'=>2]],'payment_method'=>'cash','paid_amount'=>30000])->assertStatus(422)->assertJsonValidationErrors('items.0.quantity');
        $this->assertDatabaseCount('sales', 0); $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_inactive_product_and_invalid_payment_are_rejected(): void
    {
        [$user,$product] = $this->setupProduct(10, false);
        $this->actingAs($user)->postJson(route('pos.checkout'), ['items'=>[['product_id'=>$product->id,'quantity'=>1]],'payment_method'=>'cash','paid_amount'=>30000])->assertStatus(422)->assertJsonValidationErrors('items.0.quantity');
    }
}
