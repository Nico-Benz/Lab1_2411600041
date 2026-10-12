<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Test Admin',
            'email' => 'testadmin@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->call(GradeItemSeeder::class);
    }
}