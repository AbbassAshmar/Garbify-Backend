<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Mime\Part\Multipart\AlternativePart;

class Size extends Model
{
    protected $fillable = ['size'];

    use HasFactory;

    function products(){
        return $this->belongsToMany(Product::class, "products_sizes","size_id","product_id");
    }
    public function images(){
        return $this->hasMany(Image::class);
    }
    public function reviews(){
        return $this->hasMany(Review::class);
    }
    public function orderDetails(){
        return $this->hasMany(OrderDetails::class);
    }
    // returns alternative sizes of a size (collection)
    public function alternativeSizes(){
        return $this->hasMany(AlternativeSize::class);
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
