<?php

namespace Database\Seeders;

use App\Models\ProductsImage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreateDefaultProductImage extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductsImage::create([
            'image_url'=>'defualtProductImage.png',
            'image_details'=>"default image",
            'product_id'=>null,
            'color_id'=>null,
            'size_id'=>null
        ]);
    }
}
