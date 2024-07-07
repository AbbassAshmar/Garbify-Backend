<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Helpers\ValidateResourceHelper;

use App\Services\Product\Helpers\Filters\ColorFilter;
use App\Services\Product\Helpers\Filters\SizeFilter;
use App\Services\Product\Helpers\Filters\PriceFilter;
use App\Services\Product\Helpers\Filters\SaleFilter;
use App\Services\Product\Helpers\Filters\NewArrivalFilter;
use App\Services\Product\Helpers\Filters\CategoryFilter;

use App\Helpers\GetCategoriesHelper;
use App\Helpers\GetResponseHelper;
use App\Models\AlternativeSize;
use App\Models\Color;
use App\Models\ProductsImage;
use App\Models\Sale;
use App\Models\Size;
use App\Models\Tag;
use App\Services\Product\Helpers\Filters\SearchFilter;
use Exception;
use Illuminate\Support\Facades\Storage;

class ProductService {
    private $getCategoriesHelper;

    function __construct(GetCategoriesHelper $getCategoriesHelper){
        $this->getCategoriesHelper = $getCategoriesHelper;
    }

    public function getAll($filters){
        $products =  Product::with(['category',]);

        $searchFilter = new SearchFilter($filters['search']);
        $colorFilter = new ColorFilter($filters['color'], $searchFilter);
        $sizeFilter = new SizeFilter($filters['size'],$colorFilter);
        $priceFilter = new PriceFilter($filters['price'],$sizeFilter);
        $categoryFilter = new CategoryFilter($this->getCategoriesHelper,$filters['category'], $priceFilter);
        $saleFilter = new SaleFilter($filters['sale'],$categoryFilter);
        $newArrivalFilter = new NewArrivalFilter($filters['newArrival'],$saleFilter);

        return $newArrivalFilter->filter($products);
    }

    public function getByID($id){
        $product = Product::find($id);
        ValidateResourceHelper::ensureResourceExists($product, 'Product');
        return $product;
    }

    public function getPopularProducts(){
        $products = Product::select("products.*")
        ->leftJoin("order_details", 'products.id', '=','order_details.product_id')
        ->join('orders','order_details.order_id','=','orders.id')
        ->groupBy('products.id')->orderByRaw("sum(order_details.ordered_quantity) DESC");

        return $products;
    }

    public function createProduct($validated_data){
        // fields to create a review instance 
        $data = [
            'name'=> $validated_data['name'],
            'quantity' => $validated_data['quantity'],
            'category_id'=> $validated_data['category_id'],
            'description' =>$validated_data['description'],
            'type' => $validated_data['type'],
            'original_price' => $validated_data['original_price'],
            'selling_price' => $validated_data['selling_price'],
            'status' => $validated_data['status'],
        ];

        
        try {
            $product_instance = Product::create($data);
        }catch(Exception $e){
            return null;
        }
        
        // create the thumbnail instance 
        if(isset($validated_data['thumbnail_data'])){
            $color = $validated_data['thumbnail_data']['color'];
            $image = $validated_data['thumbnail_data']['image'];
            $color_instance = Color::firstOrCreate(['color'=>$color]);

            $path = Storage::putFile('public/productImages', $image);
            $thumbnail_url = Storage::url($path); // stored at storage/public/ca.. , accessed by public/storage/ca..

            ProductsImage::create([
                'color_id'=>$color_instance->id, 
                'image_url'=>$thumbnail_url,
                'is_thumbnail' => true,
                'product_id' => $product_instance->id
            ]);
        }


        // create other images instances
        if (isset($validated_data['images_data'])){
            foreach($validated_data['images_data'] as $images_data) {
                $color_instance = Color::firstOrCreate(['color'=>$color]);

                foreach($images_data['images'] as $image){
                    $path = Storage::putFile('public/productImages', $image);
                    $imageUrl = Storage::url($path); // stored at storage/public/ca.. , accessed by public/storage/ca..

                    ProductsImage::create([
                        'color_id'=>$color_instance->id, 
                        'image_url'=>$imageUrl,
                        'is_thumbnail' => false,
                        'product_id' => $product_instance->id
                    ]);
                }
            }
        }

        // get or create tags instances
        if (isset($validated_data['tags'])){
            foreach ($validated_data['tags'] as $tag){
                $tag_instance = Tag::firstOrCreate(['name', $tag]);
                $product_instance->tags()->attach($tag_instance->id);
            }
        }

        // get or create sizes and alternative sizes for each size 
        if (isset($validated_data['sizes_data'])) {
            // Collect all unique measurement units from the sizes data
            $measurementUnits = [];
        
            foreach ($validated_data['sizes_data'] as $size_data) {
                if (isset($size_data['attributes'])) {
                    foreach ($size_data['attributes'] as $attribute) {
                        $measurementUnits[$attribute['measurement_unit']] = true;
                    }
                }
            }
        
            // Ensure each size has all measurement units
            foreach ($validated_data['sizes_data'] as &$size_data) {
                if (!isset($size_data['attributes'])) {
                    $size_data['attributes'] = [];
                }
        
                // Create a map for quick lookup of existing attributes
                $existingAttributes = [];
                foreach ($size_data['attributes'] as $attribute) {
                    $existingAttributes[$attribute['measurement_unit']] = $attribute['value'];
                }
        
                // Add missing attributes with "N/A"
                foreach ($measurementUnits as $unit => $value) {
                    if (!isset($existingAttributes[$unit])) {
                        $size_data['attributes'][] = [
                            'value' => 'N/A',
                            'measurement_unit' => $unit
                        ];
                    }
                }
            }

            foreach ($validated_data['sizes_data'] as $size_data){
                $size_instance = Size::firstOrCreate(['size'=>$size_data['value'], 'unit' => $size_data['measurement_unit']]);
                $product_instance->sizes()->attach($size_instance->id);

                if (isset($size_data['attributes'])){
                    foreach($size_data['attributes'] as $attribute){
                        $alt = AlternativeSize::firstOrCreate([
                            'size'=>$attribute['value'], 
                            'unit'=>$attribute['measurement_unit'], 
                        ]);

                        $alt->sizes()->attach($size_instance->id);
                        $alt->products()->attach($product_instance->id);
                    }
                }
            }
        }

        // get or create colors instances 
        if (isset($validated_data['colors'])){
            foreach($validated_data['colors'] as $color){
                $color_instance = Color::firstOrCreate(['color'=>$color]);
                $product_instance->colors()->attach($color_instance->id);
            }
        }   

        // create sale instance 
        if (isset($validated_data['sale']) && $validated_data['sale']){
            $sale = [
                'product_id' => $product_instance->id,
                'starts_at' => $validated_data['sale_start_date'],
                'sale_percentage' => $validated_data['discount_percentage']
            ];

            if (isset($validated_data['sale_end_date'])){
                $sale['sale_end_date'] = $validated_data['sale_end_date'];
            }       

            if (isset($validated_data['sale_quantity'])){
                $sale['sale_quantity'] = $validated_data['sale_quantity'];
            }
            
            Sale::create($sale);
        }

        return $product_instance;
    }

}

