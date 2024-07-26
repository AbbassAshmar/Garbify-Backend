<?php

namespace App\Services\Category;

use App\Helpers\ValidateResourceHelper;
use App\Models\Category;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Storage;

class CategoryService {

    public function getCategoryByID($id){
        $category = Category::find($id);
        ValidateResourceHelper::ensureResourceExists($category, "category");
        return $category;
    }

    private function storeCategoryImage($image, $oldImagePath=null, $id=null){
        $imagesPath = config("images.category");
        $imageUrl = null;

        if ($image){
            // eliminate duplicates by using content hash as a name
            $hash = md5_file($image);
            $name = $hash . "_image." . $image->extension();
            
            // if image is already stored, don't store it again
            $isImageStored = Storage::exists($imagesPath . "/" . $name);
            if (!$isImageStored){
                $path = Storage::putFileAs($imagesPath, $image, $name);
            }else{
                $path =$imagesPath . "/" . $name;
            }

            $imageUrl = Storage::url($path);
        }

        // delete old image if not used by any other category
        if ($oldImagePath && $oldImagePath != $imageUrl){
            $isImageUsedByOtherCategories = Category::where([["image_url", $oldImagePath],["id", "!=" ,$id]])->exists();
            if (!$isImageUsedByOtherCategories){
                Storage::delete($imagesPath . "/" . array_slice(explode("/",$oldImagePath), -1)[0]);
            }
        }

        return $imageUrl;
    }

    public function createCategory($validatedData){
        $data = [
            'image_url' => null,
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'display_name' => $validatedData['display_name'],
            'parent_id' => $validatedData['parent_id'] == -1 ? null :  $validatedData['parent_id'],
        ];

        if (isset($validatedData["image"])){
            $data['image_url'] = $this->storeCategoryImage($validatedData['image']);
        }

        try {
            $category = Category::create($data);
        }catch(Exception $exc){
            return ["category" => null, "error" => $exc];
        }
        
        return ["category" => $category, "error" => null];
    }

    public function updateCategory($category, $data){
        $data['parent_id'] = $data['parent_id'] == -1 ? null : $data['parent_id'];
        $directUpdateFields = [
            "name", "description", "display_name","parent_id",  
        ];

        foreach($directUpdateFields as $field){
            if (isset($data[$field])){
                $category[$field] = $data[$field];
            }
        }

        if (isset($data["image"])){
            $category->image_url = $this->storeCategoryImage($data['image'],$category->image_url, $category->id);
        }

        $category->save();
        return ["category" => $category, "error" => null];
    }


    public function listCategoriesNested(){
        $categories = Category::tree()->get()->toTree();
        return $categories;
    }

    public function listCategoriesFlat(){
        $categories = Category::with(['children'])->get();
        return $categories;
    }

    public function listCategoriesOneNestingLevel(){
        $categories = Category::with(["descendants"])->where("parent_id", null)->get();
        return $categories;
    }

    public function listSalesCategories($count=null){
        $categories = Category::withCount([
            'products as products_sale' => function ($query){
                $query->whereHas('sales',function ($subquery){
                    $now = Carbon::now();
                    $subquery->where([['starts_at', '<',$now], ['ends_at', '>',$now]])
                    ->orWhere('sales.quantity', '>' ,0);
                });
            }
        ])->orderByDesc('products_sale')->take($count)->get();
        
        return $categories;
    }

    public function listNewArrivalsCategories($count=null){
        $categories = Category::withCount([
            'products as new_products' => function ($query){
                $query->where('created_at', '>', Carbon::now()->subDays(30))
                    ->where("created_at", "<", Carbon::now()->addDays(30));
            }
        ])->orderByDesc('new_products')->take($count)->get();

        return $categories;
    }
}