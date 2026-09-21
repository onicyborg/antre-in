<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Category $category;
    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->category = Category::factory()->create();
        $this->unit = Unit::factory()->create();
    }

    public function test_product_create_records_initial_stock_movement(): void
    {
        $response = $this->actingAs($this->admin)->post(route('products.store'), $this->productData([
            'stock_initial' => 12,
        ]));

        $response->assertRedirect(route('products.index'));
        $product = Product::query()->firstOrFail();
        $this->assertSame(12, $product->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'user_id' => $this->admin->id,
            'type' => StockMovementType::Initial->value,
            'quantity_change' => 12,
            'stock_after' => 12,
        ]);
    }

    public function test_product_edit_does_not_change_stock_and_supports_image_upload(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        app(StockService::class)->recordMovement($product, $this->admin, StockMovementType::Initial, 7, 'Stok uji');
        $image = UploadedFile::fake()->image('produk.png');

        $response = $this->actingAs($this->admin)->put(route('products.update', $product), $this->productData([
            'name' => 'Produk diperbarui',
            'stock_initial' => 99,
            'image' => $image,
        ]));

        $response->assertRedirect(route('products.index'));
        $product->refresh();
        $this->assertSame(7, $product->stock);
        $this->assertSame('Produk diperbarui', $product->name);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_product_delete_is_rejected_when_stock_history_exists(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        $product->stockMovements()->create([
            'user_id' => $this->admin->id,
            'type' => StockMovementType::Initial,
            'quantity_change' => 0,
            'stock_before' => 0,
            'stock_after' => 0,
            'note' => 'Riwayat uji',
        ]);

        $this->actingAs($this->admin)->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('error', 'Produk tidak dapat dihapus karena sudah dipakai transaksi atau riwayat stok, nonaktifkan saja.');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_product_requires_unique_sku(): void
    {
        Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'sku' => 'SKU-SAMA',
        ]);

        $this->actingAs($this->admin)->from(route('products.index'))->post(route('products.store'), $this->productData(['sku' => 'SKU-SAMA']))
            ->assertRedirect(route('products.index'))
            ->assertSessionHasErrors('sku');
    }

    private function productData(array $overrides = []): array
    {
        return array_merge([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'sku' => 'SKU-'.fake()->unique()->numerify('#####'),
            'barcode' => fake()->unique()->numerify('899############'),
            'name' => 'Produk Uji',
            'cost_price' => 10000,
            'sell_price' => 15000,
            'stock_initial' => 0,
            'min_stock' => 2,
            'is_active' => 1,
        ], $overrides);
    }
}
