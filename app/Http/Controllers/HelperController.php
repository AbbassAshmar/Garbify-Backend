<?php

namespace App\Http\Controllers;
use Laravel\Sanctum\PersonalAccessToken;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Exceptions\TransactionFailedException;

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
class HelperController extends Controller
{
    public static function transaction($callback, $args){
        try{
            DB::beginTransaction();
            $result = $callback(...$args);
            DB::commit();
            return $result;
        }catch(Exception $e){
            DB::rollBack();
            throw TransactionFailedException::transactionFailed();
        }
    }

    public static function checkIfNotFound($resource,$name){
        if (!$resource){
            $error = ["message"=>"$name not found.",'code'=>404];
            $response_body = HelperController::getFailedResponse($error,null);
            return response($response_body,404);
        }
        return null;
    }

    public static function retrieveResource($resource,$name){
        $data = [$name=>$resource];
        $response_body = HelperController::getSuccessResponse($data,null);
        return response($response_body,200);
    }
    
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

    public static function getUserAndToken($request){
        $return_anonymous = ['token'=>null,'user'=>User::role('anonymous')->first()];

        $auth_header = $request->header("Authorization");

        if (!$auth_header){
            return $return_anonymous;
        }

        $plain_text_token = explode(" ",$auth_header); //retrieve plainTextToken
        if (count($plain_text_token) <2) {
            return $return_anonymous;
        }

        $token = PersonalAccessToken::findToken($plain_text_token[1]); //retrieve token
        if (!$token){
            return $return_anonymous;
        }
        
        if ($token->tokenable && !(UserController::check_token_expiry($token))){
            return ['token' => $token , 'user' =>$token->tokenable];
        }

        return $return_anonymous;
    }

    //applies sorting , pagination 
    public static function getCollectionAndCount($builder,$sort_by=null,$page_limit=null,$resource=null,$name=null){
        if (!$page_limit){
           $page_limit = ['page'=>null,'limit'=>50];
        }

        $sorted_builder = self::sortCollection($builder,$sort_by);
        $limited_sorted_builder = self::filterNumber($sorted_builder, $page_limit['page'],$page_limit['limit']);
        $result = $limited_sorted_builder['builder']->get();

        if ($name){
            $data =[$name => $resource ?  $resource::collection($result) : $result]; 
        }else {
            $data = [ $resource ?  $resource::collection($result) : $result ];
        }
        
        $returned_arr = self::getSuccessResponse($data , $limited_sorted_builder['info'] );
        return $returned_arr;
    }

    // limit count of collection according to pagination
    public static function filterNumber($builder, $page, $limit=50){
        if (!$builder) return $builder;
        if (!$page) $page = 1;
        if (!$limit) $limit = 50;
        $page = intval($page);
        $limit = intval($limit);
        $skip =($page - 1) * $limit;

        // paginate and get total_count
        $total_count = $builder->count();
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

    public static function likeOrUnlikeResource($resource ,$user, $field_name){
        $doAction = function($resource, $user, $field_name){
            $like = $resource->likes()->where("user_id", $user->id)->first();

            if ($like){
                $resource->likes()->detach($user->id);
                $data = ["action" =>"unliked"];
            }else{
                $resource->likes()->attach([$user->id]);
                $data = ["action" =>"liked"];
            }
    
            $new_count = $resource->likes()->count();
            $resource->update([$field_name => $new_count]);
            $metadata = [$field_name => $new_count];
    
            return response(self::getSuccessResponse($data,$metadata),200);
        };

        return self::transaction($doAction,[$resource,$user,$field_name]);
    }
}
