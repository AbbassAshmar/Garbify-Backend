<?php

namespace App\Services\Product\Helpers\Filters;

use App\Services\Product\Helpers\Filters\Ifilter;

class SizeFilter implements Ifilter{
    private $nextHandler;
    private $size;
    
    function __construct($size, Ifilter $nextHandler=null){
        $this->size = $size;   
        $this->nextHandler = $nextHandler;
    }

    public function filter($products){
        $size = $this->size;
        if ($this->size){ 
            $products = $products->whereHas("sizes", function($query)use(&$size){
                $query->where("size",$size);
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