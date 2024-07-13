<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoppingCart extends Model
{
    use HasFactory;

    protected $fillable=[
        'user_id', 
        'created_at',
        'updated_at',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function shoppingCartItems() {
        return $this->hasMany(shoppingCartItem::class);
    }
 
    public function getAmountSubtotalAttribute(){
        $amount_subtotal = 0 ;
        foreach($this->shoppingCartItems as $item){
            $amount_subtotal += $item->amount_subtotal;
        }
        return $amount_subtotal;
    }
    
    public function getAmountTotalAttribute(){
        
    }

    

}
