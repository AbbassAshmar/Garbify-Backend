<?php

namespace App\Services\Product\Helpers\Filters;

use App\Services\Product\Helpers\Filters\Ifilter;

class SearchFilter implements Ifilter{
    private $nextHandler;
    private $search;
    
    function __construct($search, Ifilter $nextHandler=null){
        $this->search = $search;   
        $this->nextHandler = $nextHandler;
    }

    public function filter($products){
        if ($this->search){ 
            $products = $products->where('name','like',"%$this->search%");
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