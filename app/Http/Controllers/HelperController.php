<?php

namespace App\Http\Controllers;
use Laravel\Sanctum\PersonalAccessToken;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class HelperController extends Controller
{
    const ANONYMOUS_USER_ID = 1;

    public static function getUserAndToken($request){
        $auth_header = $request->header("Authorization");
        if (!$auth_header){
            return ['token'=>null,'user'=>User::find(self::ANONYMOUS_USER_ID)];
        }

        $plain_text_token = explode(" ",$auth_header); //retrieve plainTextToken
        if (count($plain_text_token) <2) {
            return ['token'=>null,'user'=>User::find(self::ANONYMOUS_USER_ID)];
        }

        $token = PersonalAccessToken::findToken($plain_text_token[1]); //retrieve token
        if ($token->tokenable && !(UserController::check_token_expiry($token))){
            return ['token' => $token , 'user' =>$token->tokenable];
        }

        return ['token'=>null,'user'=>User::find(self::ANONYMOUS_USER_ID)];
    }

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
