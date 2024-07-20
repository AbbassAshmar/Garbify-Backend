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
        $products =  Product::with(['category','sizes','colors']);

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
        $product = Product::with(['sizes','sizes.alternativeSizes','colors','tags','category'])->find($id);
        ValidateResourceHelper::ensureResourceExists($product, 'Product');
        return $product;
    }

    public function getPopularProducts(){
        $products = Product::with(['category','sizes','colors'])->select("products.*")
        ->leftJoin("order_details", 'products.id', '=','order_details.product_id')
        ->join('orders','order_details.order_id','=','orders.id')
        ->groupBy('products.id')->orderByRaw("sum(order_details.ordered_quantity) DESC");

        return $products;
    }

    private function addThumbnailToProduct($thumbnail_data, $product){
        $color = $thumbnail_data['color'];
        $image = $thumbnail_data['image'];
        $color_instance = Color::firstOrCreate(['color'=>$color]);

        $path = Storage::putFile('public/productImages', $image);
        $thumbnail_url = Storage::url($path); // stored at storage/public/ca.. , accessed by public/storage/ca..

        ProductsImage::create([
            'color_id'=>$color_instance->id, 
            'image_url'=>$thumbnail_url,
            'is_thumbnail' => true,
            'product_id' => $product->id
        ]);
    }

    function addTagsToProduct($tags,$product){
        foreach ($tags as $tag){
            $tag_instance = Tag::firstOrCreate(['name' => $tag]);
            $product->tags()->attach($tag_instance->id);
        }
    }

    function addColorsToProduct($colors,$product){
        foreach($colors as $color){
            $color_instance = Color::firstOrCreate(['color'=>$color]);
            $product->colors()->attach($color_instance->id);
        }
    }

    function addSizesToProduct($sizes, $product){
        foreach ($sizes as $size_data){
            $size_instance = Size::firstOrCreate(['size'=>$size_data['size'], 'unit' => $size_data['unit']]);
            $product->sizes()->attach($size_instance->id);

            if (isset($size_data['alternative_sizes'])){
                foreach($size_data['alternative_sizes'] as $alternative){
                    $alt = AlternativeSize::firstOrCreate([
                        'size'=>$alternative['size'], 
                        'unit'=>$alternative['unit'], 
                    ]);

                    $alt->sizes()->attach($size_instance->id);
                    $alt->products()->attach($product->id);
                }
            }
        }
    }

    function validateAlternativeSizes(&$sizes_data){
        // Collect all unique measurement units from the sizes data
        $measurementUnits = [];
        foreach ($sizes_data as $size_data) {
            if (isset($size_data['alternative_sizes'])) {
                foreach ($size_data['alternative_sizes'] as $size) {
                    $measurementUnits[$size['unit']] = true;
                }
            }
        }
    
        // Ensure each size has all measurement units
        foreach ($sizes_data as &$size_data) {
            if (!isset($size_data['alternative_sizes'])) {
                $size_data['alternative_sizes'] = [];
            }
    
            // Create a map for quick lookup of existing alternative_sizes
            $existingAlternatives = [];
            foreach ($size_data['alternative_sizes'] as $size) {
                $existingAlternatives[$size['unit']] = $size['size'];
            }
    
            // Add missing alternative_sizes with "N/A"
            foreach ($measurementUnits as $unit => $size) {
                if (!isset($existingAlternatives[$unit])) {
                    $size_data['alternative_sizes'][] = [
                        'size' => 'N/A',
                        'unit' => $unit
                    ];
                }
            }
        }

        return $sizes_data;
    }

    function addImagesToProducts($imagesColorsList, $product){
        foreach($imagesColorsList as $images_data) {
            $color_instance = Color::firstOrCreate(['color'=>$images_data['color']]);
            foreach($images_data['images'] as $image){
                $path = Storage::putFile('public/productImages', $image);
                $imageUrl = Storage::url($path); // stored at storage/public/ca.. , accessed by public/storage/ca..

                ProductsImage::create([
                    'color_id'=>$color_instance->id, 
                    'image_url'=>$imageUrl,
                    'is_thumbnail' => false,
                    'product_id' => $product->id
                ]);
            }
        }
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
            $this->addThumbnailToProduct($validated_data['thumbnail_data'], $product_instance);
        }   


        // create other images instances
        if (isset($validated_data['images_data'])){
            $this->addImagesToProducts($validated_data['images_data'],$product_instance);
        }

        // get or create tags instances
        if (isset($validated_data['tags'])){
            $this->addTagsToProduct($validated_data['tags'],$product_instance);
        }

        // get or create colors instances 
        if (isset($validated_data['colors'])){
            $this->addColorsToProduct($validated_data['colors'],$product_instance);
        }   

        // get or create sizes and alternative sizes for each size 
        if (isset($validated_data['sizes_data'])) {
            $this->validateAlternativeSizes($validated_data['sizes_data']);
            $this->addSizesToProduct($validated_data['sizes_data'], $product_instance);
        }

        // create sale instance 
        if (isset($validated_data["sale"]) && $validated_data["sale"]){
            $sale = [
                "product_id" => $product_instance->id,
                "starts_at" => $validated_data["sale_start_date"],
                "ends_at" => $validated_data["sale_end_date"] ?? null,
                "quantity" => $validated_data["sale_quantity"] ?? null,
                "sale_percentage" => $validated_data["discount_percentage"],
                "status" => "active",
            ];
            Sale::create($sale);
        }

        return $product_instance;
    }

    public function deleteProduct($product){
        return $product->delete();
    }

    public function updateProduct($product, $data){
        // name,
        // description
        // quantity
        // status

        // original_price
        // selling_price

        // 
    }
}

