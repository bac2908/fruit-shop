<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Generate a strong default password
        // In production, change this, or better use environment variable
        $password = env('ADMIN_PASSWORD', 'Admin@12345');

        User::query()->updateOrCreate(
            ['email' => 'bacnguyen2921@gmail.com'],
            [
                'name' => 'Admin FruitShop',
                'password' => Hash::make($password),
                'role' => 'admin',
                'phone' => null,
                'address' => null,
            ]
        );

        $this->command->info('Admin user seeded: bacnguyen2921@gmail.com');
        if (env('APP_DEBUG')) {
            $this->command->warn('⚠️  Default password: ' . $password);
            $this->command->warn('⚠️  CHANGE THIS PASSWORD IMMEDIATELY IN PRODUCTION');
        }
    }
}
