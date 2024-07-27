<?php

namespace Database\Seeders;

use App\Models\SalesStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreateSaleStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'active', 'description' => "Only one sale can be active for a product at a time."],
            ['name' => 'inactive', 'description' => "Inactive sale will not be applied even if it's time has started."],
        ];

        SalesStatus::insert($statuses);
    }
}
