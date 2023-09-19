<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = ['category', 'parent_id'];
    protected $hidden = ['parent_id','created_at', 'updated_at'];

    function products(){
        return $this->hasMany(Product::class);
    }
   
}
