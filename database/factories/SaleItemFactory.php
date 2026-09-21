<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SaleItem> */
class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;
    public function definition(): array { $product = Product::factory()->create(); return ['sale_id'=>Sale::factory(),'product_id'=>$product->id,'product_name'=>$product->name,'sku'=>$product->sku,'unit_name'=>'pcs','quantity'=>1,'unit_price'=>$product->sell_price,'cost_price'=>$product->cost_price,'subtotal'=>$product->sell_price]; }
}
