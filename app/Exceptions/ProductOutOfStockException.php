<?php

namespace App\Exceptions;

use Exception;

class ProductOutOfStockException extends Exception
{
    public static function outOfStock($product){
        return new static("$product out of stock.");
    }

    public static function insufficientStock($product){
        return new static("The requested quantity for $product is not available in sufficient stock.");
    }
    
}
