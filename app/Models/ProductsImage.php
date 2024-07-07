<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductsImage extends Model
{
    use HasFactory;
    protected $appends = ['image_url'];
    protected $hidden = ['created_at', 'updated_at','color_id'];
    protected $fillable= [
        'product_id',
        'color_id',
        'is_thumbnail',
        'image_url'
    ];

    public function product(){
        return $this->belongsTo(Product::class);
    }
    
    public function color(){
        return $this->belongsTo(Color::class);
    }
 
    //overrides image_url attribute to include domain name and path
    function getImageUrlAttribute(){
        if (!isset($this->attributes['image_url']) || !$this->attributes['image_url']) 
        return null;

        return asset($this->attributes['image_url']);
    }

}

