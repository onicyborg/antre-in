<?php

namespace Database\Factories;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Sale> */
class SaleFactory extends Factory
{
    protected $model = Sale::class;
    public function definition(): array { return ['status'=>SaleStatus::Completed,'user_id'=>User::factory(),'subtotal'=>0,'discount_value'=>0,'discount_amount'=>0,'tax_percent'=>0,'tax_amount'=>0,'total'=>0,'completed_at'=>now()]; }
}
