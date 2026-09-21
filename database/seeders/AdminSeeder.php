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
        $password = 'Qwerty123*';

        $users = [
            [
                'email' => 'admin@example.com',
                'name' => 'Administrator',
                'role' => UserRole::Admin,
            ],
            [
                'email' => 'kasir@example.com',
                'name' => 'Kasir',
                'role' => UserRole::Kasir,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make($password),
                    'role' => $user['role'],
                    'is_active' => true,
                ],
            );
        }

        $this->command?->info('Akun awal berhasil disiapkan: admin@example.com dan kasir@example.com.');
    }
}
