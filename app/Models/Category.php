<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class Category extends Model
{
    use HasFactory;
    use HasRecursiveRelationships;

    protected $fillable = ['name', 'description', 'parent_id', 'display_name', 'image_url'];
    protected $appends = ["image_url", "total_products", "total_sales"];
    protected $hidden = ['created_at', 'updated_at'];

    function products(){
        return $this->hasMany(Product::class);
    }


    // accessors 

    function getImageUrlAttribute(){
        if (!isset($this->attributes['image_url']) || !$this->attributes['image_url']) 
        return null;

        return asset($this->attributes['image_url']);
    }

    function getTotalSalesAttribute(){
        return $this->products()
            ->join('order_details', 'products.id', '=', 'order_details.product_id')
            ->where('products.category_id', $this->id)
            ->sum('order_details.amount_total');
    }

    function getTotalProductsAttribute(){
        return $this->products()->count();
    }

    
}
