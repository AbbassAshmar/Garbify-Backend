<?php

namespace Database\Seeders;

use App\Models\ProductStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreateProductStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'out of stock', 'description' => "Product is set to out of stock, even if stock available"],
            ['name' => 'in stock', 'description' => "Product is set in stock, even if stock is unavailable"],
        ];

        ProductStatus::insert($statuses);
    }
}
