<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $cost = fake()->numberBetween(5000, 100000);

        return [
            'category_id' => Category::factory(),
            'unit_id' => Unit::factory(),
            'sku' => 'SKU-'.fake()->unique()->numerify('######'),
            'barcode' => fake()->unique()->numerify('899############'),
            'name' => fake()->unique()->words(3, true),
            'cost_price' => $cost,
            'sell_price' => $cost + fake()->numberBetween(1000, 30000),
            'min_stock' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
