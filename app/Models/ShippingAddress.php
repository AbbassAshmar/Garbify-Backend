<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    use HasFactory;

    protected $fillable=[
    'user_id', 
    'country', 
    'city' , 
    'state' , 
    'postal_code', 
    'address_line_1' , 
    'address_line_2' ,
    'recipient_name',
    'phone_number',
    'email',
    'created_at'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function orders(){
        return $this->hasMany(Order::class);
    }
}
