<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
    // spatie rules 
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function reviews(){
        return $this->hasMany(Review::class);
    }
    public function liked_reviews(){
        return $this->belongsToMany(Review::class, "reviews_users",'user_id','review_id');
    }
    public function orders(){
        return $this->hasMany(Order::class);
    }
    public function shippingAddresses(){
        return $this->hasMany(ShippingAddress::class);
    }

    public function favoritesList(){
        return $this->hasOne(FavoritesList::class);
    }

    public function viewedFavoritesLists(){
        return $this->belongsToMany(User::class, "favorites_list_views","user_id","favorites_list_id");
    }

    public function likedFavoritesLists(){
        return $this->belongsToMany(User::class, "favorites_list_likes","user_id","favorites_list_id");
    }

    public function setPasswordAttribute($password){
        $this->attributes['password'] = Hash::make($password);
    }
}
