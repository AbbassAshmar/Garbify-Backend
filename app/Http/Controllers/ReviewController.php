<?php

namespace App\Http\Controllers;

use App\Exceptions\TransactionFailedException;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\ProductController;
use App\Models\Review;
use App\Models\Size;
use App\Models\Color;
use App\Models\ReviewsImage;
use Exception;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Exceptions\UnauthorizedException;

class ReviewController extends Controller
{
    // returns all reviews of a product 
    function listReviewsByProduct(Request $request , $product_id){
        $user_token =  HelperController::getUserAndToken($request);
        $current_user = $user_token["user"];

        $pageLimit = ['page'=>$request->input("page"), 'limit'=> $request->input('limit')];
        $sort_by = $request->input("sort-by") ? $request->input("sort-by") : "helpful_count DESC";
        
        $product = Product::find($product_id);
        HelperController::checkIfNotFound($product, "Product");
        
        $reviews = $product->reviews();
        $average_ratings = floatval($reviews->avg("product_rating"));

        $response = HelperController::getCollectionAndCount(
            $reviews,
            $sort_by,
            $pageLimit,
            null,
            'reviews'
        );
        $response['data']['reviews'] = ReviewResource::collection_with_user(
            $response['data']['reviews'], 
            $current_user
        ); 
        $response['metadata']['average_rating'] = $average_ratings;
        return response($response, 200);
    }

    function deleteReview(Request $request , $id){ 
        $user = $request->user();
        $review = Review::find($id);        
        HelperController::checkIfNotFound($review,"Review");
        
        if ($user->id != $review->user->id && ! $user->hasPermissionTo('delete_any_review')){
            throw new UnauthorizedException(403,'You do not have the required authorization.');
        }

        $product = $review->product;
        $review->delete();
        $reviews_of_current_product = $product->reviews;
        
        $average_ratings = floatval($reviews_of_current_product->avg("product_rating"));

        $data = ['action'=>'deleted'];
        $metadata = [
            'average_ratings' => $average_ratings,
            'total_count'=>$reviews_of_current_product->count()
        ];
        $response = HelperController::getSuccessResponse($data,$metadata);
        return response($response,200);
    }

    // returns whether a user has created a review of a product or not 
    function checkIfUserReviewed(Request $request, $product_id){
        $user = $request->user();
        
        $product = Product::find($product_id);
        if (!$product) return response(['message' => 'product not found.'],400);

        $review = Review::where([["user_id",$user->id] , ["product_id", $product_id]])->first();
        if (!$review) {
            $response = HelperController::getSuccessResponse(['reviewed'=>false],null);
            return response($response, 200);
        }

        $response = HelperController::getSuccessResponse(['reviewed'=>true],null);
        return response($response, 200);
    }


    // user likes a review of a product
    function likeReview(Request $request, $id){
        $user = $request->user();
        $review = Review::find($id);
        HelperController::checkIfNotFound($review , "Review");
        return HelperController::likeOrUnlikeResource($review, $user, 'helpful_count');
    }
    
    // create a review 
    function createReview(Request $request){
        $validated_data = $request->validate([
            'images' =>['bail','max:3','nullable'],
            'images.*' => ['bail','max:5000','nullable','mimes:jpeg,png,jpg'],
            'title' => ['bail', 'required','string'],
            'text' =>['bail','required', 'string'], 
            'product_id'=>['bail','required','integer'],
            'product_rating' =>['bail','required','integer'],
            'user_height' =>['bail','nullable','string', 'max:256'],
            'user_weight' =>['bail','nullable','string','max:256'],
            'size' =>['bail','nullable','string','max:256'],
            'color' =>['bail','nullable','string','max:256'],
        ],[
            'images.max' => 'The maximum amount of images allowed is 3.'
        ]);

        // get the user instance 
        $user = $request->user();

        //get the product instance 
        $product =Product::find($validated_data['product_id']);
        HelperController::checkIfNotFound($product,"Product");
        
        // check if the user has a review on the product 
        $review = Review::where([["user_id", $user->id],['product_id',$product->id]])->first();
        if ($review) {
            $error = ['message'=>"You have already reviewed this product.", 'code'=>400];
            $response_body = HelperController::getFailedResponse($error,null);
            return response($response_body,400);
        }

        // fields to create a review instance 
        $data = [
            'title'=> $validated_data['title'],
            'text' =>$validated_data['text'],
            'product_id'=> $product->id,
            'user_id'=> $user->id,
            'product_rating' => $validated_data['product_rating'],
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
            $error = ['message'=>'Something unexpected happened.', 'code'=>400];
            $response_body = HelperController::getFailedResponse($error,null);
            return response($response_body,400);
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

        $data= ['action'=>'created'];
        $response_body = HelperController::getSuccessResponse($data,null);
        return response($response_body,201);
    }
}
