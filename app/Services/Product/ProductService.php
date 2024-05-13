<?php

namespace App\Services\Product;

use App\Models\Product;

use App\Services\Product\Helpers\Filters\ColorFilter;
use App\Services\Product\Helpers\Filters\SizeFilter;
use App\Services\Product\Helpers\Filters\PriceFilter;
use App\Services\Product\Helpers\Filters\SaleFilter;
use App\Services\Product\Helpers\Filters\NewArrivalFilter;
use App\Services\Product\Helpers\Filters\CategoryFilter;

use App\Helpers\GetCategoriesHelper;

class ProductService {
    private $getCategoriesHelper;

    function __construct(GetCategoriesHelper $getCategoriesHelper){
        $this->getCategoriesHelper = $getCategoriesHelper;
    }

    public function getAll($filters){
        $products =  Product::with([]);
        
        $colorFilter = new ColorFilter($filters['color']);
        $sizeFilter = new SizeFilter($filters['size'],$colorFilter);
        $priceFilter = new PriceFilter($filters['price'],$sizeFilter);
        $categoryFilter = new CategoryFilter($this->getCategoriesHelper,$filters['category'], $priceFilter);
        $saleFilter = new SaleFilter($filters['sale'],$categoryFilter);
        $newArrivalFilter = new NewArrivalFilter($filters['newArrival'],$saleFilter);

        return $newArrivalFilter->filter($products);
    }

}

