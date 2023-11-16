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
        $user_token =  HelperController::getUserAndToken($request);
        $current_user = $user_token["user"];

        $page = $request->input("page");
        $limit = $request->input('limit');
        $sort_by = $request->input("sort-by") ? $request->input("sort-by") : "helpful_count DESC";
        $product = Product::find($product_id);

        if (!$product)
            return response(["message"=>"Product not found."], 400);

        $reviews = $product->reviews();
        $average_ratings = floatval($reviews->avg("product_rating"));

        $response = HelperController::getCollectionAndCount($reviews,$sort_by,$page,$limit);
        $response['data'] = ReviewResource::collection_with_user($response['data'], $current_user); 
        $response['metadata']['average_rating'] = $average_ratings;
       
        return response($response, 200);
    }

    function deleteReview(Request $request , $id){
        // delete a review 
        $user = $request->user();
        $review = Review::find($id);
        if (!$review){
            return response(['message'=>"Review does not exist."],400);
        }
        if ($user->id != $review->user->id){
            return response();
        }
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
    function likeReview(Request $request, $id){
        $user = $request->user();
        $review = Review::find($id);

        if (!$review) return response(["message"=>"Review not found."], 404);

        $check_like_exists = $review->likes()->where("user_id", $user->id)->first();
        if ($check_like_exists){
            $review->likes()->detach($user->id); //remove the like
            $new_likes_count = $review->likes()->count(); //get the new count
            $review->update(['helpful_count'=>$new_likes_count]); //update the old count
            return response(['helpful_count'=> $new_likes_count, 'action'=>"removed"],200);
        }

        $review->likes()->attach([$user->id]);
        $new_likes_count = $review->likes()->count();
        $review->update(['helpful_count'=>$new_likes_count]);
        return response(['helpful_count'=> $new_likes_count, 'action'=>"added"],200);
    }

    // returns whether a user has created a review of a product or not 
    function checkIfUserReviewed(Request $request, $product_id){
        $user = $request->user();
        
        $product = Product::find($product_id);
        if (!$product) return response(['message' => 'product not found.'],400);

        $review = Review::where([["user_id",$user->id] , ["product_id", $product_id]])->first();
        if (!$review) return response(["reviewed" => false],200);

        return response(['reviewed'=>true], 200);
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
        if (!$product) return response(['message'=>'Product not found.'], 404);
        
        // check if the user has a review on the product 
        $review = Review::where([["user_id", $user->id],['product_id',$product->id]])->first();
        if ($review) return response(['message'=>"you have already reviewed this product."],400);
        
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
                // store the image in storage/app/public/reviewsImages dir (public/reviewsImages/)
                // to access it ,use public/storage (storage is a symlink connects public/storage to storage/app/public/reviewsImages)
                // public directory is the only directory accessible from outside , static files are stored in it 
                // accessing storage/app/public directly is not allowed (even in url like host/storage/app/public) instead use host/symlink
                $path = $image->storeAs('public/reviewsImages/',$name);
                // store image's name in the db
                $image = ReviewsImage::create(['review_id'=>$review->id,'image_url'=>basename($path)]);
            }
        }

        return response(['created' => 'true'],201);
    }
}
