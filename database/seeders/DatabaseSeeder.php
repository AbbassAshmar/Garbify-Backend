<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CreateAnonymousUserSeeder::class,
            CreateOrderStatusSeeder::class,
            CreateProductStatusSeeder::class,
            CreateSaleStatusSeeder::class,
            UserRolePermissionSeeder::class,
        ]);
    }
}
