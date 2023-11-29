<?php

namespace App\Models;

use App\Http\Controllers\HelperController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;
    protected $fillable= [
        "created_at",
        'user_height',
        'user_weight',
        'user_id',
        'product_id',
        'title',
        'text',
        'size_id',
        'color_id',
        'helpful_count',
        'product_rating'
    ];
    
    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function size(){
        return $this->belongsTo(Size::class);
    }

    public function color(){
        return $this->belongsTo(Color::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function likes(){
        return $this->belongsToMany(User::class, "reviews_users",'review_id','user_id');
    }
    public function reviewsImages(){
        return $this->hasMany(ReviewsImage::class);
    }

    public function getReviewImagesListAttribute(){
        $res = [];
        $images = $this->reviewsImages();
        foreach($images as $image){
            array_push($res, $image->image_url);
        }
        return $res;
    }

    public function IsLikedByCurrentUser($current_user=null){
        // if no user is logged in or anonymous user ,return false
        if (!$current_user || $current_user->hasRole("anonymous")){
            return False;
        }
        $liked  = $this->likes()->where("user_id" , $current_user->id)->first();
        return $liked ? true : false ;
    }
}
