<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewsImage extends Model
{
    use HasFactory;

    protected $fillable = ['review_id','image_url','created_at'];
    protected $appends = ['image_url'];

    public function review(){
        return $this->belongsTo(Review::class);
    }

    public function image_url(){
        return asset('/storage/reviewsImages/'.$this->attributes['image_url']);
    }
}
