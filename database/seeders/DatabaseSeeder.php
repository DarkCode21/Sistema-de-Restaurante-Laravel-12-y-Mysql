<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            TableSeeder::class,
            PaymentMethodSeeder::class,
            PermissionSeeder::class,
            UserSeeder::class,
            SettingSeeder::class
        ]);        
    }
}
