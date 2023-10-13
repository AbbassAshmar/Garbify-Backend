<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HelperController extends Controller
{
    public static function getCollectionAndCount($builder,$sort_by, $page, $limit,$name){
        $total_count = $builder->count();
        $sorted_builder = self::sortCollection($builder,$sort_by);
        $limited_sorted_builder = self::filterNumber($sorted_builder, $page,$limit);
        $result = $limited_sorted_builder->get();
        $count_after_limit = $result->count();
        $returned_arr = [
            $name => $result,
            "count" => $count_after_limit,
            "total_count" => $total_count
        ];
        return $returned_arr;
    }

    // limit count of collection according to pagination
    public static function filterNumber($builder, $page, $limit=50){
        if (!$builder) return $builder;
        if (!$page) $page = 1;
        if (!$limit) $limit = 50;
        $builder = $builder->skip(($page-1) * $limit)->take($limit);
        return $builder;
    }

    public static function sortCollection($collection, $sort_by){
        // + : space 
        if (!$collection || !$sort_by) return $collection;
        
        try{
            $sort_by = str_replace("+"," ",$sort_by);
            $collection->orderByRaw($sort_by);
            // check if excuting throws an exception (for sorting with nonexistent columns)
            $collection->get();
            return $collection;
        }
        catch(Exception $asc){
            DB::rollBack();
            $collection->getQuery()->orders=null;
            return $collection;
        }
    }
}
