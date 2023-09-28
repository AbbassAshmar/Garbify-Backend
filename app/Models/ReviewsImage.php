<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewsImage extends Model
{
    use HasFactory;

    protected $fillable = ['review_id','image_url','created_at'];
    public function review(){
        return $this->belongsTo(Review::class);
    }
}
