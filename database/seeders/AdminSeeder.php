<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) env('ADMIN_EMAIL', ''));
        $password = (string) env('ADMIN_PASSWORD', '');

        if ($email === '' || $password === '') {
            $this->command?->warn('AdminSeeder dilewati: ADMIN_EMAIL dan ADMIN_PASSWORD wajib diisi pada .env.');

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator',
                'password' => Hash::make($password),
                'role' => UserRole::Admin,
                'is_active' => true,
            ],
        );

        $this->command?->info('Admin awal berhasil disiapkan.');
    }
}
