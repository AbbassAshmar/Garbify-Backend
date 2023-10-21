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
    protected $fillable =['name','user_id', 'created_at', 'views_count','likes_count','public'];
    // protected $appends = ["is_liked_by_current_user"];

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

    public function IsLikedByCurrentUser($current_user=null){
        // if no user is logged in or anonymous user ,return false
        
        if (!$current_user || $current_user->id == HelperController::getANONYMOUS_USER_ID()){
            return False;
        }
        $liked  = $this->likes()->where("user_id" , $current_user->id)->first();
        return $liked ? true : false ;
    }
 
}
