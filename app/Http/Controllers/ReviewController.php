<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\ProductController;
use App\Models\Review;
use App\Models\Size;
use App\Models\Color;
use App\Models\ReviewsImage;
use Exception;

class ReviewController extends Controller
{
    // returns all reviews of a product 
    function reviewsByProduct(Request $request , $product_id){
        $page = $request->input("page");
        $limit = $request->input('limit') ? $request->input('limit') : 10;
        $sort_by = $request->input("sort-by") ? $request->input("sort-by") : "helpful_count-DESC";
        $product = Product::find($product_id);
        if (!$product)
            return response(["error"=>"product not found"], 404);
        $reviews = $product->reviews();
        $total_count = $reviews->count();
        $average_ratings = floatval($reviews->avg("product_rating"));
        $reviews_sorted = ProductController::sortCollection($reviews,$sort_by);
        $reviews_sorted_limited = ProductController::filterNumber($reviews_sorted,$page,$limit);
        $result = $reviews_sorted_limited->get();
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

    // create a review 
    function createReview(Request $request){
        $validated_data = $request->validate([
            'images' =>['bail','max:3','nullable'],
            'images.*' => ['bail','max:5000','nullable','mimes:doc,pdf,docx,zip,jpeg,png,jpg,gif,svg'],
            'title' => ['bail', 'required','string'],
            'text' =>['bail','required', 'string'], 
            'product_id'=>['bail','required','string'],
            'product_rating' =>['bail','required','string'],
            'user_height' =>['bail','nullable','string', 'max:256'],
            'user_weight' =>['bail','nullable','string','max:256'],
            'size' =>['bail','nullable','string','max:256'],
            'color' =>['bail','nullable','string','max:256'],
        ]);

        // get the user instance 
        $user = $request->user();

        //get the product instance 
        $product =Product::find((int)$validated_data['product_id']);
        if (!$product) return response(['error'=>'product does not exist'], 400);
        
        // check if the user has a review on the product 
        $review = Review::where([["user_id", $user->id],['product_id',$product->id]])->first();
        if ($review) return response(['error'=>"you have already reviewed this product."],400);
        
        // fields to create a review instance 
        $data = [
            'title'=> $validated_data['title'],
            'text' =>$validated_data['text'],
            'product_id'=> $product->id,
            'user_id'=> $user->id,
            'product_rating' => (int)$validated_data['product_rating'],
            'size_id' => null,
            'color_id' =>null,
            'user_height' =>null,
            'user_weight' =>null
        ];

        // add the size_id to data if size in request and not null
        if (isset($validated_data['size'])) 
        $data['size_id'] = Size::where('size',$validated_data['size'])->first()->id;
       
        // add the color_id to data if color in request and not null
        if (isset($validated_data['color']))
        $data['color_id'] = Color::where('color',$validated_data['color'])->first()->id;

        // add user_height if in the request and not null
        if (isset($validated_data['user_height']))
        $data['user_height'] = $validated_data['user_height'];

        // add user_weight if in the request and not null
        if (isset($validated_data['user_weight']))
        $data['user_weight'] = $validated_data['user_weight'];

        // create a review instance
        try {
            $review = Review::create($data);
        }catch(Exception $e){
            return response(['error'=>$e->getMessage()],400);
        }

        // create images instances from the images in the request
        if ($request->hasFile("images")){
            foreach($validated_data['images'] as $image){
                // random name for the image
                $name = $image->hashName();
                // store the image in storage/app/public/reviewsImages dir
                // to access it , use public/storage (storage is a symlink)
                $image->storeAs('public/reviewsImages/',$name);
                // store image's name in the db
                $image = ReviewsImage::create(['review_id'=>$review->id,'image_url'=>$name]);
            }
        }

        return response(['created' => 'true'],201);
    }
}
