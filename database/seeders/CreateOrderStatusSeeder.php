<?php

namespace Database\Seeders;

use App\Models\OrdersStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreateOrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'paid', 'description' => "initial status, client has just paid"],
            ['name' => 'awaiting shipment', 'description' => "Admin accepted the order, and now waiting to be picked by the shipper"],
            ["name" => "declined", 'description' => "Admin decided not to sell his item and cancelled order , money is returned to the customer"],
            ['name' => 'shipping', 'description' => "Order picked by shipper and on it's way to the customer"],
            ["name" => "completed", "description" => "Order has been picked by the customer from the shipper"],
            ["name" => "on hold", "description" => "Some problem happend when shipping / or with seller and is being solved"],
            ["canceled" => "on hold", "description" => "Before shipping status, customer can cancel order and get his money back"],
        ];

        OrdersStatus::insert($statuses);
    }
}
