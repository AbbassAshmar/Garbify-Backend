<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlternativeSize extends Model
{
    use HasFactory;

    protected $fillable = ['size','unit'];
    protected $hidden = ['created_at', 'updated_at','pivot'];

    public function sizes(){
        return $this->belongsToMany(Size::class, 'sizes_alternative_sizes', 'alternative_size_id','size_id');

    }

    public function products(){
        return $this->belongsToMany(Product::class, 'products_alternative_sizes', 'alternative_size_id', 'product_id');
    }

}
