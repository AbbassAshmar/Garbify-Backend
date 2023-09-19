<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable=[
        'created_at',
        'status',
        'total_cost',
        'tax_cost',
        'prodcuts_cost',
        'user_id',
        'shipping_address_id',
        'shipping_method_id',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function shippingAddress(){
        return $this->belongsTo(ShippingAddress::class);
    }
    
    public function shippingMethod(){
        return $this->belongsTo(ShippingMethod::class);
    }

    public function orderDetails(){
        return $this->hasMany(OrderDetail::class);
    }
}
