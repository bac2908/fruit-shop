<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $email = trim((string) env('ADMIN_EMAIL'));
        $password = (string) env('ADMIN_PASSWORD');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('ADMIN_EMAIL must be a valid email before running AdminUserSeeder.');
        }

        if (!$this->isStrongPassword($password)) {
            throw new \RuntimeException(
                'ADMIN_PASSWORD must have at least 12 characters, upper/lowercase letters, a number and a symbol.'
            );
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin FruitShop',
                'password' => Hash::make($password),
                'role' => 'admin',
                'phone' => null,
                'address' => null,
                'password_changed_at' => now(),
                'force_password_change' => false,
            ]
        );

        $this->command->info('Admin user seeded: ' . $email);
    }

    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 12
            && preg_match('/[a-z]/', $password)
            && preg_match('/[A-Z]/', $password)
            && preg_match('/\d/', $password)
            && preg_match('/[^A-Za-z0-9]/', $password)
            && !in_array($password, ['Admin@12345', 'Admin@123456'], true);
    }
}
