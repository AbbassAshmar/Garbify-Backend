<?php

namespace App\Services\Product\Helpers\Filters;

use App\Services\Product\Helpers\Filters\Ifilter;
use DateTime;

class SaleFilter implements Ifilter{
    private $nextHandler;
    private $sale;
    
    function __construct($sale, Ifilter $nextHandler=null){
        $this->sale = $sale;   
        $this->nextHandler = $nextHandler;
    }

    public function filter($products){
        if ($this->sale == 'true') {
            $products = $products->whereHas("sales", function($query){
                $query->where('starts_at' , '<', (new DateTime())->format('Y-m-d H:i:s') )->where('ends_at' , '>', (new DateTime())->format('Y-m-d H:i:s'));
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