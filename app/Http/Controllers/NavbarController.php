<?php

namespace App\Http\Controllers;

use App\Http\Resources\NavbarCollection;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Resources\NavbarResource;
class NavbarController extends Controller
{
    public function show(Request $request){
        $main_categories= Category::where("parent_id", null)->get();
        $main_children = array_map(function($cat){
            return [
                "name"=>$cat->category,
                "children"=>Category::select("category")->where('parent_id',$cat->id)->get()->all()
            ];
        },$main_categories->all());

        $sales = ["name"=>"sales" , "children"=>$main_children];
        $new_arraivals = ["name"=>"new arrivals" ,"children"=> $main_children];   
        
        $response = NavbarResource::collection($main_categories)->toArray($request);
        array_push($response,$sales,$new_arraivals);
        return $response;
    }
}
