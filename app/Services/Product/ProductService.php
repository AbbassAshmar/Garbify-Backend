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
use App\Models\ProductStatus;
use App\Models\Sale;
use App\Models\Size;
use App\Models\Tag;
use App\Services\Product\Helpers\Filters\SearchFilter;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

    private function setProductThumbnail($thumbnail_data, $product){
        $color = $thumbnail_data['color'];
        $image = $thumbnail_data['image'];
        
        $color_instance = Color::firstOrCreate(['color'=>$color]);
        $thumbnail_url = $this->storeProductImage($image);

        $hasThumbnail = $product->thumbnail->exists();
        if (!$hasThumbnail){
            ProductsImage::create([
                'color_id'=>$color_instance->id, 
                'image_url'=>$thumbnail_url,
                'is_thumbnail' => true,
                'product_id' => $product->id
            ]);
            return;
        }

        $isThumbnailDifferent = $product->thumbnail->image_url != $thumbnail_url;
        if ($isThumbnailDifferent){
            $this->deleteProductImages(collect($product->thumbnail));
            ProductsImage::create([
                'color_id'=>$color_instance->id, 
                'image_url'=>$thumbnail_url,
                'is_thumbnail' => true,
                'product_id' => $product->id
            ]);
        }else{
            if ($product->thumbnail->color_id != $color_instance->id){
                $product->thumbnail->color_id = $color_instance->id;
                $product->thumbnail->save();
            }
        }
    }

    function setProductTags($tags,$product){
        $tagsIDs = [];
        foreach ($tags as $tag){
            $tag_instance = Tag::firstOrCreate(['name' => $tag]);
            array_push($tagsIDs, $tag_instance->id);
        }

        $product->tags()->sync($tagsIDs);
    }

    function setProductColors($colors,$product){
        $colorsIDs = [];
        foreach($colors as $color){
            $color_instance = Color::firstOrCreate(['color'=>$color]);
            array_push($colorsIDs, $color_instance->id);
        }

        $product->colors()->sync($colorsIDs);
    }

    private function updateCurrentSale($data, $product){
        // Check if a current sale exists
        $sale = $product->current_sale;
    
        if ($sale) {
            if (isset($data['sale_end_date'])) {
                $sale->ends_at = $data['sale_end_date'];
            }
    
            if (isset($data['sale_start_date'])) {
                $sale->starts_at = $data['sale_start_date'];
            }
    
            if (isset($data['sale_quantity'])) {
                $sale->quantity = $data['sale_quantity'];
            }
    
            if (isset($data['discount_percentage'])) {
                $sale->sale_percentage = $data['discount_percentage'];
            }
    
            $sale->save();
        }
    }

    private function setCurrentSale($saleData, $product) {
        // Create a new sale
        $newSale = [
            "product_id" => $product->id,
            "starts_at" => $saleData["sale_start_date"],
            "ends_at" => $saleData["sale_end_date"] ?? null,
            "quantity" => $saleData["sale_quantity"] ?? null,
            "sale_percentage" => $saleData["discount_percentage"],
            "status" => "active",
        ];

        Sale::create($newSale);
    }

    function setProductSizes($sizes, $product){
        $sizesIDs = [];
        $allAlternativeSizesIDs = [];

        foreach ($sizes as $size_data){
            $size_instance = Size::firstOrCreate([
                'size'=>$size_data['size'], 
                'unit' => $size_data['unit']
            ]);
            array_push($sizesIDs, $size_instance->id);

            if (isset($size_data['alternative_sizes'])){
                $alternativeSizesIDs = [];
                foreach($size_data['alternative_sizes'] as $alternative){
                    $alternativeSize = AlternativeSize::firstOrCreate([
                        'size'=>$alternative['size'], 
                        'unit'=>$alternative['unit'], 
                    ]);

                    $alternativeSize->sizes()->attach($size_instance->id);
                    $alternativeSize->products()->attach($product->id);

                    array_push($alternativeSizesIDs, $alternativeSize->id);
                    array_push($allAlternativeSizesIDs, $alternativeSize->id);
                }
                $size_instance->alternativeSizes()->attch($alternativeSizesIDs);          
            }
        }

        $product->alternativeSizes()->sync($allAlternativeSizesIDs);
        $product->sizes()->attach($sizesIDs);
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

    function storeProductImage($image){
        $imagesPath = config("images.product");

        // hash content of the image, use the hash as a name for uniqueness.
        $hashImageContent = md5_file($image);
        $name = $hashImageContent . "_image.". $image->extension();
        
        $isImageStored = Storage::exists($imagesPath . $name);
        if (!$isImageStored){
            $path = Storage::putFileAs($imagesPath, $image, $name);
        }else{
            $path = $imagesPath . "/" . $name;
        }

        return Storage::url($path);
    }

    // takes a list of ProductImages and deletes them, 
    // if an image is not used by other ProductImages, it also deletes it
    function deleteProductImages($images){
        $imagesPath = config("images.product");

        foreach($images as $image){
            $usedByOthers = ProductsImage::where([["image_url", $image->image_url],["id", "!=", $image->id]])->exists();
            if (!$usedByOthers){
                $imageName = array_slice(explode("/",$image->image_url), -1)[0];
                Storage::delete($imagesPath . "/" . $imageName);
            }
        }

        $images->delete();
    }

    function setProductImages($imagesColorsList, $product){
        $productImages = [];

        foreach($imagesColorsList as $images_data) {
            $color = Color::firstOrCreate(['color'=>$images_data['color']]);
            foreach($images_data['images'] as $image){
                $imageUrl = $this->storeProductImage($image);
                array_push($productImages, $imageUrl);

                // check if the image already belongs to the product
                $currentImage = $product->cover_images()->where([['image_url', $imageUrl]])->get();
                if ($currentImage->exists()){
                    if ($currentImage->color_id != $color->id){
                        $currentImage->color_id = $color->id;
                        $currentImage->save();
                    }
                }else{
                    ProductsImage::create([
                        'color_id'=>$color->id, 
                        'image_url'=>$imageUrl,
                        'is_thumbnail' => false,
                        'product_id' => $product->id
                    ]);
                }
            }
        }

        // delete useless product_images instances not present in the list
        // if an image is not used by other products, delete it from storage
        $removedImages = $product->cover_images()->whereNotIn(["image_url", $productImages])->get();
        $this->deleteProductImages($removedImages);
    }

    // if a size is related to an alternative and no common product between them, end their relation
    private function clearSizesAndAlternativesRelations($sizes){
        foreach($sizes as $size){
            $productsOfCurrentSize = $size->products->pluck('id')->toArray();
            $alternativeSizes = $size->alternativeSizes()->whereDoesntHave('products', function ($query) use ($productsOfCurrentSize) {
                $query->whereIn('id', $productsOfCurrentSize);
            });

            $size->detach($alternativeSizes->pluck("id")->toArray());
        }
    }

    public function createProduct($validated_data){
        // fields to create a review instance 
        $data = [
            'name'=> $validated_data['name'],
            'quantity' => $validated_data['quantity'],
            'category_id'=> $validated_data['category_id'],
            'status_id' => $validated_data['status_id'],
            'description' =>$validated_data['description'],
            'type' => $validated_data['type'],
            'original_price' => $validated_data['original_price'],
            'selling_price' => $validated_data['selling_price'],
        ];

        try {
            $product_instance = Product::create($data);
        }catch(Exception $exc){
            return ["product" => null, "error" => $exc];
        }

        // create the thumbnail instance 
        if(isset($validated_data['thumbnail_data'])){
            $this->setProductThumbnail($validated_data['thumbnail_data'], $product_instance);
        }
    
        // create other images instances
        if (isset($validated_data['images_data'])){
            $this->setProductImages($validated_data['images_data'],$product_instance);
        }

        // get or create tags instances
        if (isset($validated_data['tags'])){
            $this->setProductTags($validated_data['tags'],$product_instance);
        }

        // get or create colors instances 
        if (isset($validated_data['colors'])){
            $this->setProductColors($validated_data['colors'],$product_instance);
        }   

        // get or create sizes and alternative sizes for each size 
        if (isset($validated_data['sizes_data'])) {
            $this->validateAlternativeSizes($validated_data['sizes_data']);
            $this->setProductSizes($validated_data['sizes_data'], $product_instance);
        }

        // create sale instance 
        if (isset($validated_data["sale"]) && $validated_data["sale"]){
            $this->setCurrentSale($validated_data['sale'], $product_instance);
        }

        return ["product" => $product_instance, "error" => null];
    }

    public function updateProduct($product, $data){
        $directUpdateFields = [
            "name", "description", "quantity",'category_id',
            "original_price", "selling_price","type",'status_id'
        ];

        foreach($directUpdateFields as $field){
            if (isset($data[$field])){
                $product[$field] = $data[$field];
            }
        }

        if (isset($data["tags"])){
            $this->setProductTags($data['tags'], $product);
        }

        if (isset($data["colors"])){
            $this->setProductColors($data['colors'], $product);
        }

        if (isset($data['sizes_data'])){
            $oldSizes = $product->sizes;

            if (!$data['sizes']){
                $sizes = $product->sizes->pluck("size")->toArray();
                $sizesInSizesData = array_map(function($size){
                    return $size['size'];
                },$data['sizes_data']);

                if ($sizes != $sizesInSizesData){
                    throw ValidationException::withMessages([
                        'sizes_data' => 'Main sizes in sizes data should be the same as the sizes of the product.',
                    ]);
                }
            }

            $this->validateAlternativeSizes($data['sizes_data']);
            $this->setProductSizes($data['sizes_data'], $product);
            $this->clearSizesAndAlternativesRelations($oldSizes);
        }

        if(isset($data['thumbnail_data'])){
            $this->setProductThumbnail($data['thumbnail_data'], $product);
        }   

        if (isset($data['images_data'])){
            if (!$data['colors']){
                $colors = $product->colors->pluck("color")->toArray();
                $colorsInImagesData = array_map(function($imageData){
                    return $imageData['color'];
                },$data['images_data']);

                if ($colors != $colorsInImagesData){
                    throw ValidationException::withMessages([
                        'images_data' => 'Colors of images should be the same as colors of the product.',
                    ]);
                }
            }

            $this->setProductImages($data['images_data'],$product);
        }
        
        $this->updateCurrentSale($data, $product);

        $save = $product->save();
        return $save ? $product : null;
    }

    public function deleteProduct($product){
        return $product->delete();
    }
}

