<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable=[
        'created_at',
        'status',
        'amount_total',
        'amount_tax',
        'amount_subtotal',
        'percentage_tax',
        'canceled_at',
        'user_id',
        'shipping_address_id',
        'shipping_method_id',
        'payment_intent_id'
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

    //ACCESSORS 

    // created at : 0-2 days -> shipping in ... days
    //              2-max-shipping-days -> Delivering in ... days
    //              > max_shipping_days -> delivered
    public function getShippingStatusAttribute(){
        $created_at_since = $this->OrderCreatedAtSinceDays();
        $max_shipping_days = $this->shippingMethod->max_days;
       
        if ($created_at_since < 2 )
            return "Shipping in ". (2-$created_at_since) ." days";
        
        if ($created_at_since == 2)
            return "Shipping today";

        if ($created_at_since < $max_shipping_days)
            return "Delivering in". ($max_shipping_days - $created_at_since) ." days";
        
        if ($created_at_since == $max_shipping_days)
            return "Delivering today !";

        return "Delivered at ". (new Carbon($this->created_at))->addDays($max_shipping_days);
    }

    public function getReturnCancellationInfoAttribute(){
        $created_at_since = $this->OrderCreatedAtSinceDays();
        $max_shipping_days = $this->shippingMethod->max_days;
        if ($created_at_since < 2 )
            return "cancellation available till ".(new Carbon($this->created_at))->addDays(2);

        $last_return_day =(new Carbon($this->created_at))->addDays(30+$max_shipping_days);

        if (Carbon::now() < $last_return_day )
            return "return available till ". $last_return_day;
        return "return unavailable";
    }

    public function getCanBeCanceledAttribute(){
        // status where the order can be fully or partially canceled
        if ($this->status == "Paid" || $this->status == "Awaiting shipment"){
            return true;
        }
        return false;
    }

    public function getNumberOfUncanceledProductsAttribute(){
        return $this->orderDetails()->where('canceled_at' , null)->count();
    }
    //HELPERS

    // returns number of days since creating an order
    public function OrderCreatedAtSinceDays(){
        $now = Carbon::now();
        $created_at = new Carbon($this->created_at);
        return $now->diffInDays($created_at);
    }
}

