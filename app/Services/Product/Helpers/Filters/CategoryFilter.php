<?php

namespace App\Services\Product\Helpers\Filters;

use App\Services\Product\Helpers\Filters\Ifilter;
use App\Helpers\GetCategoriesHelper;

class CategoryFilter implements Ifilter{
    private $nextHandler;
    private $categories;
    private $getCategoriesHelper;
    
    function __construct(GetCategoriesHelper $getCategoriesHelper,$categories, Ifilter $nextHandler=null){
        $this->categories = $categories;   
        $this->nextHandler = $nextHandler;
        $this->getCategoriesHelper = $getCategoriesHelper;
    }

    public function filter($products){
        if ($this->categories){ 
            $products = $this->filterCategories($products,$this->categories);
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


    //returns all products of categories[last_element] and it's children
    //if no categories provided, category doesn't exist, it returns all products
    private function filterCategories($products, array $categories=[]){
        if (count($categories)<= 0) return $products;

        // get the children of every category in $categories
        $categories_array = $this->getCategoriesHelper->getChildrenOfCategory($this->getCategoriesHelper->getChildByParents($categories));
        
        $ids_array = [];
        foreach($categories_array as $category){
            array_push($ids_array,$category->id);
        }

        // filter based on the children and parents array 
        if ($ids_array && count($ids_array) > 0){
            $products = $products->whereHas("category" , function($query) use (&$ids_array){
                $query->whereIn("id", $ids_array);
            });
        }

        return $products;
    }


}