<?php


namespace App\Helpers;

use App\Models\Category;

class GetCategoriesHelper {
    // returns the descendents of a collection of categories 
    public function getChildrenOfCategories (array $categoryArr){
        $result = [];
        foreach($categoryArr as $category_name){
            $category_obj = Category::where('category', $category_name)->first();
            $result = array_merge($result , $this->getChildrenOfCategory($category_obj));
        }
        return $result;
    }

    // [men, shoe, running] returns category object named "running" that is a child of shoes that is a child of men
    public function getChildByParents(array $categories){
       
        $array_length = count($categories);
        $parent = $categories[0];
     
        $parent_obj = Category::where('category' , $parent)->first();
        if (!$parent_obj || $array_length == 1) return Category::where("category",$parent)->first();
        $parent_id =$parent_obj->id; 
        $temp= [];
        for ($i = 1 ; $i<$array_length;$i+=1){
            $ctg=Category::where('parent_id',$parent_id)->where("category",$categories[$i])->first();
            array_push($temp, $ctg);

            // category doesn't exist
            if (!$ctg){
                $parent_id = null;
                break;
            }

            $parent_id = $ctg->id;
        }
        return Category::find($parent_id);
    }

    // returns the descendents of a single category
    public function getChildrenOfCategory($category){
        if(!($category instanceof Category)) return [];

        //array of children of $category
        $children = Category::where('parent_id', $category->id)->get()->all();
        if (!$children) return [$category]; 

        // get the children of each child
        $resultArr = [];
        foreach($children as $child){
            $resultArr= array_merge($resultArr, $this->getChildrenOfCategory($child));
        }
        
        array_push($resultArr,$category);
        return $resultArr; 
    }
}