<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable =['favorites_list_id', 'product_id' , 'created_at'];

    public function favoritesList(){
        return $this->belongsTo(favoritesList::class);
    }

    public function product(){
        return $this->belongsTo(Product::class);
    }


}
