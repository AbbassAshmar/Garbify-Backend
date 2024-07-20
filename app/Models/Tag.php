<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name'];
    protected $hidden = ['created_at', 'updated_at','pivot'];

    public function products(){
        return $this->belongsToMany(Tag::class,"products_tags", "tag_id","product_id");
    }
}
