<?php

namespace App\Http\Controllers;

use App\Helpers\GetCategoriesHelper;
use App\Helpers\GetResponseHelper;
use App\Helpers\ValidateResourceHelper;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\Product\ProductService;

use App\Http\Requests\CreateProductRequest;
// get() returns a collection with conditions (where() orderBy()..) : returns QuerySet
// first() returns the first instance                               : returns Object
// find() finds based on id , find(1) similar to first()            : returns Object
// all() returns a collection without conditions                    : returns QuerySet

// others return object builders (can't be used without using get() after)


class ProductController extends Controller{
    private $productService;

    function __construct(ProductService $productService){
        $this->productService = $productService;
    }

    public function listProducts(Request $request){
        $color = $request->input("color");
        $size = $request->input("size");
        $price = $request->input("price");
        $categories= $request->input("categories") ?$request->input("categories"):[];
        $sort_by = $request->input("sort");
        $pageLimit = ['page'=>$request->input("page"),'limit'=>$request->input("limit")];
        $sales = $request->input("sales");
        $new_arrivals = $request->input("new-arrivals");
        $search = $request->input("q");

        $filters = [
            'search'=>$search,
            'sale'=>$sales,
            'newArrival' =>$new_arrivals,
            'color'=>$color,
            'size' =>$size,
            'price'=>$price,
            'category' =>$categories,
        ];

        $products = $this->productService->getAll($filters);
        $products = GetResponseHelper::processCollectionFormatting(
            $products,
            $sort_by,
            $pageLimit,
            null,
            'products'
        );

        return response($products, 200) ;
    }

    public function listPopularProducts(Request $request){
        $limit = $request->input("limit");
        $page= $request->input("page");

        $products = $this->productService->getPopularProducts();
        $products = GetResponseHelper::processCollectionFormatting(
            $products,
            ['page'=>$page,'limit'=>$limit],
            null,
            null,
            'products'
        );

        return response($products,200);
    }
    
    public function createProduct(CreateProductRequest $request){
        $data = $request->validated();
        $product = $this->productService->createProduct($data);

        if ($product == null){
            $error = ['message'=>'Something unexpected happened.', 'code'=>400];
            $response_body = GetResponseHelper::getFailedResponse($error,null);
            return response($response_body,400);
        }

        $payload = GetResponseHelper::getSuccessResponse(['action' => 'created'],null);
        return response($payload, 201);
    }

    public function retrieveProduct(Request $request , $id){
        $product = $this->productService->getByID($id);
        $product = (new ProductResource($product))->toArray($request);
        $response = GetResponseHelper::getSuccessResponse(['product'=>$product],null);
        return response($response, 200);
    }

    public function productSize(Request $request , $id){
        $product = $this->productService->getByID($id);
        $response = GetResponseHelper::getSuccessResponse(['sizes'=>$product->sizes_array],null);
        return response($response,200);
    }

    public function productColor(Request $request ,$id){
        $product = $this->productService->getByID($id);
        $response = GetResponseHelper::getSuccessResponse(['sizes'=>$product->colors_array],null);
        return response($response,200);
    }
}
