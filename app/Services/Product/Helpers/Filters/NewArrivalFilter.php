<?php

namespace App\Services\Product\Helpers\Filters;

use App\Services\Product\Helpers\Filters\Ifilter;

class NewArrivalFilter implements Ifilter{
    private $nextHandler;
    private $new;
    
    function __construct($new, Ifilter $nextHandler=null){
        $this->new = $new;   
        $this->nextHandler = $nextHandler;
    }

    public function filter($products){
        if ($this->new == 'true') {
            $products = $products->orderBy("created_at","DESC");
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