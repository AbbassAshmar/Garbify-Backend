<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ProductController;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;

class FilterController extends Controller{
    private function getResponse($categories, $colors, $sizes,$price){
        return  [
            [
                "name"=>"category",
                "type"=>"list",
                'options' => array_map(function($cat){return $cat->category;},$categories)
            ],
            [
                'name' =>"color",
                'type' =>'list',
                'options' => array_map(function($color){return $color->color;},$colors)
            ],
            [
                'name' =>"size",
                'type' =>'list',
                'options' => array_map(function($size){return $size->size;},$sizes)
            ],
            [
                'name'=>'price',
                'type'=>'list',
                'options'=>[
                    "under " . (($price['min']+$price['avg'])/2)."$",
                    (($price['min']+$price['avg'])/2)."$" . " to " . $price['avg']."$",
                    "{$price['avg']}$ to ". (($price['avg']+$price['max'])/2) . "$",
                    "over " . (($price['avg']+$price['max'])/2) . "$"
                ]
            ]

        ];
    }

    // /api/filters or /api/filters?categories[]=men
    function show(Request $request){
        $category = $request->input("categories");
        if (!$category || count($category) == 0){
            $price = [
                'min'=>intval(Product::min("price")),
                'max'=> intval(Product::max("price")),
                'avg'=> intval(Product::avg("price"))
            ];
            $main_categories = Category::where("parent_id",null)->get()->all();
            $main_colors = Color::all()->all();
            $main_sizes = Size::all()->all();
            return response($this->getResponse($main_categories, $main_colors,$main_sizes,$price),200);
        }

        $cat = (new ProductController())->getChildByParents($category);
        $children = (new ProductController())->getSingleCategoryChildren($cat);
        $ids = [];
        foreach($children as $child){
            array_push($ids, $child->id);
        }
   
        $price = [
            'min'=>intval(Product::whereIn("category_id" , $ids)->min("price")),
            'max'=> intval(Product::whereIn("category_id" , $ids)->max("price")),
            'avg'=> intval(Product::whereIn("category_id" , $ids)->avg("price"))
        ];

        $colors = Color::select("color")->whereHas("products", function($query) use(&$ids){
            $query->whereIn('category_id',$ids);
        })->get()->all();

        $sizes = Size::select("size")->whereHas("products", function($query) use(&$ids){
            $query->whereIn('category_id',$ids);
        })->get()->all();
        array_pop($children);   

        return response($this->getResponse($children, $colors,$sizes,$price),200);
    }
}
