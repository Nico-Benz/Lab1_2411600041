<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Every account below uses the password: password
     */
    public function run(): void
    {
        $users = [
            ['test@example.com', 'Test User', 'admin'],
            ['manager@example.com', 'Test Manager', 'manager'],
            ['staff@example.com', 'Test Staff', 'staff'],
            ['viewer@example.com', 'Test Viewer', 'viewer'],
        ];

        foreach ($users as [$email, $name, $role]) {
            User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'role' => $role,
                ]
            );
        }

        $this->call([
            SupplierSeeder::class,
            ProductSeeder::class,
            PurchaseOrderSeeder::class,
        ]);
    }
}
