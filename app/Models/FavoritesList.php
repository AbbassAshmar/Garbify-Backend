<?php

namespace App\Models;

use App\Http\Controllers\HelperController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// belongsTo -> hasMany (one to many)

// belongsToMany -> belongsToMany (many to many)
class FavoritesList extends Model
{
    use HasFactory;
    protected $with =["user"];
    protected $appends=['thumbnail'];
    protected $fillable =['name','user_id', 'created_at', 'views_count','likes_count','public','thumbnail'];
    protected $updatable = ['name','public','thumbnail'];
    // protected $appends = ["is_liked_by_current_user"];

    public function getUpdatable(){
        return $this->updatable;
    }
    public function user(){
        return $this->belongsTo(User::class);
    }

    public function favorites(){
        return $this->hasMany(Favorite::class);
    }

    public function views(){
        return $this->belongsToMany(User::class, "favorites_list_views","favorites_list_id","user_id");
    }

    public function likes(){
        return $this->belongsToMany(User::class, "favorites_list_likes","favorites_list_id","user_id");
    }

    public function isLikedByCurrentUser($current_user=null){
        // if no user is logged in or anonymous user ,return false
        if (!$current_user || $current_user->hasRole("anonymous")) return False;

        $liked  = $this->likes()->where("user_id" , $current_user->id)->first();
        return $liked ? true : false ;
    }

    public function getThumbnailAttribute(){
        if ($this->attributes['thumbnail'])
        return asset('/storage/favoritesListsThumbnails/'.$this->attributes['thumbnail']);

        //if no thumbnail return first favorite thumb
        $products_thumb = $this->favorites()->first()->product->thumbnail; 
        if ($products_thumb && !str_ends_with($products_thumb->image_url,"defaultProductImage.png")) 
        return $products_thumb->image_url;

        // return default
        return asset('/storage/favoritesListsThumbnails/'."defaultFavoritesListThumbnail.png"); 
    }
 
}
