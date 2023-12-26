<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\Size;
use App\Models\Color;
use App\Models\ShoppingCart;
class ShoppingCartItem extends Model
{
    use HasFactory;

    protected $fillable=[
        'shopping_cart_id', 
        'created_at',
        'updated_at',
        'product_id',
        'size_id',
        'color_id',
        'quantity',
        'expires_at',
    ];

    // relations

    public function shoppingCart() {
        return $this->belongsTo(ShoppingCart::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function size() {
        return $this->belongsTo(Size::class);
    }

    public function color() {
        return $this->belongsTo(Color::class);
    }

    // accessors 

    // get current price 
    public function getAmountUnitAttribute(){
        return $this->product->current_price;
    }

    public function getAmountTotalAttribute(){
        
    }

    public function getAmountSubtotalAttribute(){
        return $this->product->current_price * $this->quantity;
    }

}
