<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    protected $fillable = ['color'];
    use HasFactory;

    public function products(){
        return $this->belongsToMany(Product::class,"colors_products", "color_id","product_id");
    }

    public function images(){
        return $this->hasMany(ProductsImage::class);
    }
    
    public function reviews(){
        return $this->hasMany(Review::class);
    }
    
    public function orderDetails(){
        return $this->hasMany(OrderDetail::class);
    }

    public function shoppingCartItems(){
        return $this->hasMany(ShoppingCartItem::class);
    }
}
