<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\ProductController;
use App\Models\Review;

class ReviewController extends Controller
{
    // returns all reviews of a product 
    function reviewsByProduct(Request $request , $product_id){
        $page = $request->input("page");
        $limit = $request->input('limit') ? $request->input('limit') : 10;
        $sort_by = $request->input("sort-by") ? $request->input("sort-by") : "helpful_count-DESC";
        $product = Product::find($product_id);
        if (!$product){
            return response(["error"=>"product not found"], 404);

        }
        $reviews = $product->reviews();
        $total_count = $reviews->count();
        $average_ratings = floatval($reviews->avg("product_rating"));
        $reviews_limited = ProductController::filterNumber($reviews,$page,$limit);
        $reviews_sorted = ProductController::sortProducts($reviews_limited,$sort_by);
        $result = $reviews_sorted->get();
        $count = $result->count();

        $resp = [
            "reviews"=>ReviewResource::collection($result)->toArray($request),
            "total_count" => $total_count,
            "average_ratings" => $average_ratings,
            "count"=>$count
        ];
        return response($resp, 200);
    }

    // returns all liked reviews (ids) by a user of a product 
    function likedReviewsByProduct(Request $request , $product_id) {
        $ids= [];
        $reviews=$request->user()->liked_reviews()->where("product_id",$product_id)->select("reviews.id")->get()->all();
        foreach ($reviews as $review){
            array_push($ids,$review->id);
        }
        return response($ids,200);
    }
 
    // user likes a review of a product
    function likeReviewByProduct(Request $request, $id){
        $user = $request->user();
        $review = Review::find($id);
        if (!$review) return response(["error"=>"review not found."], 404);

        $check_like_exists = $user->liked_reviews()->find($id);
        if (!$check_like_exists){
            $user->liked_reviews()->attach([$review->id]);
            $review->update(['helpful_count'=>$review->helpful_count+1]);
            return response(['helpful_count'=> $review->helpful_count, 'action'=>"like added"]);
        }
        $user->liked_reviews()->detach($id);
        $review->update(['helpful_count'=>$review->helpful_count-1]);
        return response(['helpful_count'=> $review->helpful_count, 'action'=>"like removed"]);
    }

    function createReview(Request $request){
        
    }
}
