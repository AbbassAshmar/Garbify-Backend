<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;
    protected $fillable = ['product_id' , 'starts_at', 'ends_at' , 'sale_percentage','quantity'];
    protected $hidden = ["created_at", "updated_at","id",'product_id'];
    // protected $appends= ['starts_at_8601'];

    function product(){
        return $this->belongsTo(Product::class);
    }
    
    public function getStartsAt8601Attribute(){
        $date = DateTime::createFromFormat('yyyy-mm-dd hh:mi:ss',$this->starts_at);
        return $this->starts_at->format('c');
    }

    public function getPriceAfterSaleAttribute(){
        return $this->product->price - ( $this->product->price * ($this->sale_percentage / 100));
    }

    public function checkIfQuantitySufficient($demand){
        if (!$this->quantity){
            return true;
        }
        return $this->quantity - $demand >= 0;
    }
}
