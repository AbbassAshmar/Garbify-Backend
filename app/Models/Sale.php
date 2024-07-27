<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;
    
    protected $appends = ['status'];
    protected $hidden = ["created_at", "updated_at","id",'product_id'];
    protected $fillable = ['product_id' , 'starts_at','status_id', 'ends_at' , 'sale_percentage','quantity'];

    public function saleStatus(){
        return $this->belongsTo(SalesStatus::class);
    }

    public function product(){
        return $this->belongsTo(Product::class);
    }
    

    // accessors
    public function getStatusAttribute(){
        return $this->saleStatus->name;
    }

    public function getStartsAt8601Attribute(){
        $date = DateTime::createFromFormat('yyyy-mm-dd hh:mi:ss',$this->starts_at);
        return $this->starts_at->format('c');
    }

    public function getPriceAfterSaleAttribute(){
        return $this->product->selling_price - ( $this->product->selling_price * ($this->sale_percentage / 100));
    }

    public function checkIfQuantitySufficient($demand){
        if (!$this->quantity){
            return true;
        }
        return $this->quantity - $demand >= 0;
    }
}
