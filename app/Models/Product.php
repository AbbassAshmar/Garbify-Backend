<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductsImage;

class Product extends Model
{
    use HasFactory;
    protected $appends =[
        'thumbnail',
        'current_sale',
        'reviews_summary',
    ];

    protected $fillable = [
        'category_id' , 
        'name', 
        'created_at',
        'original_price', 
        'selling_price', 
        'description', 
        'type', 
        'quantity',
        'status',
    ];

    protected $hidden = [
        "category_id",
    ];

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function sales(){
        return $this->hasMany(Sale::class);
    }

    public function colors(){
        return $this->belongsToMany(Color::class,"colors_products", "product_id","color_id");
    }

    public function sizes(){
        return $this->belongsToMany(Size::class, "products_sizes","product_id","size_id");
    }

    public function alternativeSizes(){
        return $this->belongsToMany(AlternativeSize::class, "products_alternative_sizes","product_id","alternative_size_id");
    }

    public function tags(){
        return $this->belongsToMany(Tag::class,"products_tags", "product_id","tag_id");
    }

    public function reviews(){
        return $this->hasMany(Review::class);
    }

    public function images(){
        return $this->hasMany(ProductsImage::class);
    }

    public function orderDetails(){
        return $this->hasMany(OrderDetail::class);
    }

    public function favorites(){
        return $this->hasMany(Favorite::class);
    }

    public function shoppingCartItems(){
        return $this->hasMany(ShoppingCartItem::class);
    }
    
    // Accessors
    public function getThumbnailAttribute(){
        $thumbnail = $this->images()->with('color')->where("is_thumbnail",true)->first();
        return $thumbnail;
    }

    public function getCoverImagesAttribute(){
        return $this->images()->where("is_thumbnail",false)->get();
    }

    public function getCurrentSaleAttribute(){
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $sale = $this->sales()->where([['starts_at' , '<=' ,$now],['ends_at','>',$now],['quantity',null],['status','active']])
        ->orWhere([['starts_at' , '<=' ,$now ],['ends_at','>',$now],['quantity','>','0'],['status','active']])
        ->orWhere([['starts_at', "<=", $now], ['quantity','>',0],['ends_at',null], ['status','active']])
        ->orderBy('starts_at','DESC')->first();
        return $sale;
    }

    public function getCurrentPriceAttribute(){
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $sale = $this->sales()->where([['starts_at' , '<=' ,$now ],['ends_at','>',$now]])->orderBy('starts_at','DESC')->first();
        if ($sale) {
            return $sale->price_after_sale;
        }
        return $this->price;
    }

    public function averageRatings(){
        return $this->reviews->avg("product_rating");
    }

    public function reviewsCount(){
        return $this->reviews->count();
    }

    public function getReviewsSummaryAttribute(){
        return [
            'average_ratings'=> $this->averageRatings(),
            'reviews_count' =>$this->reviewsCount(),
        ];
    }

}
