<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductsImage extends Model
{
    use HasFactory;
    protected $appends = ['image_url'];
    protected $fillable= [
        'image_details',
        'product_id',
        'color_id',
        'size_id',
        'is_thumbnail',
        'image_url'
    ];

    public function product(){
        return $this->belongsTo(Product::class);
    }
    public function color(){
        return $this->belongsTo(Color::class);
    }
    public function size(){
        return $this->belongsTo(Size::class);
    }

    //acessors

    //overrides image_url attribute to include domain name and path
    public function getImageUrlAttribute(){
        return asset('/storage/productsImages/'.$this->attributes['image_url']);        
    }
}


// belongsTo -> hasMany 

// bolongsToManY -> many to many relationship