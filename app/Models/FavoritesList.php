<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// belongsTo -> hasMany (one to many)

// belongsToMany -> belongsToMany (many to many)
class FavoritesList extends Model
{
    use HasFactory;

    protected $fillable =['name','user_id', 'created_at', 'views_count','likes_count','public'];
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
 
}
