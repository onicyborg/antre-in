<?php

namespace Database\Factories;

use App\Models\NumberSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NumberSequence> */
class NumberSequenceFactory extends Factory
{
    protected $model = NumberSequence::class;
    public function definition(): array { return ['type'=>'invoice','date'=>now()->toDateString(),'last_number'=>0]; }
}
