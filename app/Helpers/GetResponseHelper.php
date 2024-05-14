<?php


namespace App\Helpers;

use App\Helpers\SortingHelper;
use App\Helpers\PaginationHelper;

class GetResponseHelper {
    public static function getSuccessResponse($data,$metadata){
        $response= [ 
            "status" => "success",
            "error" => null,
            "data" => $data,
            "metadata"=> $metadata
        ];
        return $response;
    }

    public static function getFailedResponse($error,$metadata){
        $response = [
            "status" => "failed",
            "error" => $error,
            "data" => null,
            "metadata" => $metadata
        ];
        return $response;
    }

    //applies sorting , pagination , format
    public static function processCollectionFormatting($builder,$sort_by=null,$page_limit=null,$resource=null,$name=null){
        if (!$page_limit){
           $page_limit = ['page'=>null,'limit'=>50];
        }

        $sorted_builder = SortingHelper::sortCollection($builder,$sort_by);
        $limited_sorted_builder = PaginationHelper::paginateCollectionBuilder($sorted_builder, $page_limit['page'],$page_limit['limit']);
        $result = $limited_sorted_builder['builder']->get();

        if ($name){
            $data =[$name => $resource ?  $resource::collection($result) : $result]; 
        }else {
            $data = [$resource ?  $resource::collection($result) : $result ];
        }
        
        $returned_arr = self::getSuccessResponse($data , $limited_sorted_builder['info'] );
        return $returned_arr;
    }

    public static function processDataFormating($resource,$name){
        $data = [$name=>$resource];
        $response_body = self::getSuccessResponse($data,null);
        return response($response_body,200);
    }

}

// {
//     "status": "success",
//     "error": null,
//     "data": {
//         "posts": [
//             // list of posts 
//         ]
//     },
//     "metadata": []
// }
// {
//     "status": "failed",
//     "error": {
//         "code": 123,
//         "message": ["Failed to like/unlike the post."]
//     },
//     "data": null,
//     "metadata": []
// }
// actions like like 
// {
//     "status": "success",
//     "error": null,
//     "data": {
//         'action' => 'liked'
//     },
//     "metadata": []
// }
// {
//     "status": "success",
//     "error": null,
//     "data": {
//         'action' => 'unliked'
//     },
//     "metadata": []
// }
// Successful PATCH request
// {
//     "status": "success",
//     "error": null,
//     "data": {
//         "post": {
//             "id": 123,
//             // Updated fields only
//             "title": "Updated Title",
//             "content": "Updated Content"
//         }
//     },
//     "metadata": []
// }