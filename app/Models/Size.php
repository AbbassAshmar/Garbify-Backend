<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Mime\Part\Multipart\AlternativePart;

class Size extends Model
{
    protected $fillable = ['size','unit'];
    protected $hidden = ['created_at', 'updated_at','pivot'];
    
    use HasFactory;

    function products(){
        return $this->belongsToMany(Product::class, "products_sizes","size_id","product_id");
    }

    public function alternativeSizes(){
        return $this->belongsToMany(AlternativeSize::class, 'sizes_alternative_sizes', 'size_id','alternative_size_id');
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
    
    //returns all units of alternative sizes of a size (array)
    public function getAlternativeUnitsAttribute(){
        $arr = [];
        $altsizes = $this->alternativeSizes()->select("unit")->orderBy("id")->get();
        foreach ($altsizes as $size){
            array_push($arr, $size->unit);
        }
        return $arr;
    }
}
