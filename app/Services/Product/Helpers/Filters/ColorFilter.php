<?php

namespace App\Services\Product\Helpers\Filters;

use App\Services\Product\Helpers\Filters\Ifilter;

class ColorFilter implements Ifilter{
    private $nextHandler;
    private $color;
    
    function __construct($color, Ifilter $nextHandler=null){
        $this->color = $color;   
        $this->nextHandler = $nextHandler;
    }

    public function filter($products){
        $color = $this->color;
        if ($this->color){ 
            $products = $products->whereHas("colors",function($query) use(&$color) {
                $query->where("color", $color);
            });
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