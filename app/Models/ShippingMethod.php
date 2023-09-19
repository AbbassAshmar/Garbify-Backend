<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable=[
        'name', 
        'cost', 
        'min_days' , 
        'max_days' , 
        'created_at'
    ];
    public function orders(){
        return $this->hasMany(Order::class);
    }
}
