<?php

namespace Database\Factories;

use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StoreSetting> */
class StoreSettingFactory extends Factory
{
    protected $model = StoreSetting::class;
    public function definition(): array { return ['store_name'=>'antre-in','address'=>null,'phone'=>null,'receipt_footer'=>'Terima kasih atas kunjungan Anda','tax_percent'=>0,'draft_expire_hours'=>24,'max_active_drafts'=>20]; }
}
