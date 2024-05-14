<?php

namespace App\Helpers;

class PaginationHelper{

    // limit count of collection according to pagination
    public static function paginateCollectionBuilder($builder, $page, $limit=50){
        if (!$builder) return $builder;
        $total_count = $builder->count();

        if (!$page) $page = 1;
        if (!$limit) $limit = $total_count;

        $page = intval($page);
        $limit = intval($limit);
        $skip =($page - 1) * $limit;

        // paginate and get total_count
        $limited_builder = $builder->skip($skip)->take($limit);
        $pages_count =  ceil( $total_count / $limit);

        // calculate count after pagination 
        $countAfterPagination = max(0, $total_count - $skip);
        $countAfterPagination = min($countAfterPagination, $limit);

        $result = [
            'builder' => $limited_builder, 
            'info'=>[
                'count'=>$countAfterPagination,
                'total_count'=>$total_count,
                'pages_count' => $pages_count,
                'current_page'=>$page,
                'limit'=>$limit, 
            ]
        ];

        return $result;
    }
}