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

    public function createCategory($validatedData){
        $data = [
            'image_url' => null,
            "category" => $validatedData['name'],
            'parent_id' => $validatedData['parent_id'],
            'description' => $validatedData['description'],
            'display_name' => $validatedData['display_name'],
        ];

        if (isset($validatedData["image"])){
            $image = $validatedData['image'];
            $path = Storage::putFile('public/categoriesImages', $image);
            $imageUrl = Storage::url($path); // stored at storage/public/ca.. , accessed by public/storage/ca..
            $data['image_url'] = $imageUrl;
        }

        if ($data["parent_id"] == -1){
            $data["parent_id"] = null;
        }

        try{
            $category = Category::create($data);
            return ["category" => $category, "error" => null];
        }catch(Exception $exc){
            return ["category" => null, "error" => $exc];
        }
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