<?php

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->info('DemoSeeder dilewati karena environment bukan local.');

            return;
        }

        $categories = collect(['Minuman', 'Makanan', 'Camilan', 'Kebutuhan Rumah', 'Perawatan Diri'])
            ->mapWithKeys(fn (string $name) => [$name => Category::firstOrCreate(['name' => $name])]);
        $units = collect(['pcs', 'botol', 'box'])
            ->mapWithKeys(fn (string $name) => [$name => Unit::firstOrCreate(['name' => $name])]);

        $kasirPassword = (string) env('DEMO_KASIR_PASSWORD', '');
        if ($kasirPassword === '') {
            $kasirPassword = Str::random(16);
        }

        $kasir = User::updateOrCreate(
            ['email' => 'kasir.demo@example.test'],
            [
                'name' => 'Kasir Demo',
                'password' => Hash::make($kasirPassword),
                'role' => UserRole::Kasir,
                'is_active' => true,
            ],
        );

        $names = [
            'Kopi Susu Gula Aren', 'Teh Melati Botol', 'Air Mineral', 'Jus Jambu', 'Cokelat Panas',
            'Nasi Goreng', 'Mie Goreng', 'Roti Tawar', 'Susu UHT', 'Biskuit Cokelat',
            'Keripik Singkong', 'Kacang Panggang', 'Wafer Keju', 'Permen Mint', 'Cokelat Batang',
            'Sabun Cuci Piring', 'Tisu Wajah', 'Kantong Sampah', 'Pewangi Lantai', 'Spons Cuci',
            'Sabun Mandi', 'Sampo Botol', 'Pasta Gigi', 'Sikat Gigi', 'Tisu Basah',
            'Kopi Bubuk', 'Gula Pasir', 'Beras Premium', 'Minyak Goreng', 'Saus Sambal',
        ];

        $stockService = app(StockService::class);
        foreach ($names as $index => $name) {
            $cost = 5000 + (($index + 1) * 750);
            $product = Product::updateOrCreate(
                ['sku' => 'DEMO-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'category_id' => $categories->values()[$index % $categories->count()]->getKey(),
                    'unit_id' => $units->values()[$index % $units->count()]->getKey(),
                    'barcode' => '899000000'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'name' => $name,
                    'cost_price' => $cost,
                    'sell_price' => $cost + 2500 + (($index % 4) * 1000),
                    'min_stock' => $index % 3 === 0 ? 10 : 5,
                    'is_active' => true,
                ],
            );

            if ((int) $product->stock === 0) {
                $stockService->recordMovement($product, $kasir, StockMovementType::Initial, 5 + (($index * 3) % 36), 'Data demo lokal');
            }
        }

        $this->command?->info('Demo lokal dibuat: 5 kategori, 3 satuan, 30 produk, dan 1 kasir demo.');
        $this->command?->warn("Kredensial kasir demo: kasir.demo@example.test / {$kasirPassword}");
    }
}
