<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlternativeSize extends Model
{
    use HasFactory;

    protected $fillable = ['size','unit', 'size_id'];

    public function size(){
        return $this->belongsTo(Size::class);
    }
}
