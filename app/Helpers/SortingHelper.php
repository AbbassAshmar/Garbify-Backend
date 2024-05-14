<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Exception;

class SortingHelper{

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