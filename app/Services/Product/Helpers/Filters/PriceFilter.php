<?php

namespace App\Services\Product\Helpers\Filters;

use App\Services\Product\Helpers\Filters\Ifilter;

class PriceFilter implements Ifilter{
    private $nextHandler;
    private $price;
    
    function __construct($price, Ifilter $nextHandler=null){
        $this->price = $price;   
        $this->nextHandler = $nextHandler;
    }

    public function filter($products){
        if ($this->price){ 
            $price =explode("-", $this->price);
            $min = $price[0];
            $max = null;

            if (count($price)>=2) $max = $price[1];
            if ($min > 0) $products = $products->where("price",">=",$min);
            if ($max) $products = $products->where("price","<=",$max);
        }

        if ($this->nextHandler){
            return $this->nextHandler->filter($products);
        }else {
            return $products;
        }
    }

    public function setNextHandler(Ifilter $nextHandler){
        $this->nextHandler = $nextHandler;
    }

}