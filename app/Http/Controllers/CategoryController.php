<?php

namespace App\Http\Controllers;

use App\Services\Category\CategoryService;
use Illuminate\Http\Request;
use App\Helpers\GetResponseHelper;

use App\Http\Requests\CreateCategoryRequest;
use App\Http\Requests\PatchCategoryRequest;

class CategoryController extends Controller
{
    private $categoryService;

    public function __construct(CategoryService $categoryService = null) {
        $this->categoryService = $categoryService;
    }

    public function createCategory(CreateCategoryRequest $request){
        $data = $request->validated();
        ['category' => $category,'error' => $error] = $this->categoryService->createCategory($data);

        if ($category == null && $error){
            $error = ['message'=>$error, 'code'=>400];
            $data = ['action' => "not created"];
            $response_body = GetResponseHelper::getFailedResponse($error,null, $data);
            return response($response_body,400);
        }

        $payload = GetResponseHelper::getSuccessResponse(['action' => 'created'],null);
        return response($payload, 201);
    }

    public function patchCategory(PatchCategoryRequest $request, $id){
        $data = $request->validated();

        $category = $this->categoryService->getCategoryByID($id);
        ['category' => $category,'error' => $error] = $this->categoryService->updateCategory($category, $data);

        if ($category == null && $error){
            $error = ['message'=>$error, 'code'=>400];
            $data = ['action' => "not updated"];
            $response_body = GetResponseHelper::getFailedResponse($error,null, $data);
            return response($response_body,400);
        }

        $payload = GetResponseHelper::getSuccessResponse(['action' => 'updated'],null);
        return response($payload, 200);
    }

    public function getCategory(Request $request, $id){
        $category = $this->categoryService->getCategoryByID($id);
        $payload = GetResponseHelper::getSuccessResponse(['category' => $category],null);
        return response($payload, 200);
    }

    public function listCategoriesNested(Request $request){
        $categories = $this->categoryService->listCategoriesNested();
        $payload = GetResponseHelper::getSuccessResponse(['categories' => $categories],null);
        return response($payload, 200);
    }

    public function listCategoriesFlat(Request $request){
        $categories = $this->categoryService->listCategoriesFlat();
        $payload = GetResponseHelper::getSuccessResponse(['categories' => $categories],null);
        return response($payload, 200);
    }

    public function listCategoriesOneNestingLevel(Request $request){
        $categories = $this->categoryService->listCategoriesOneNestingLevel();
        $payload = GetResponseHelper::getSuccessResponse(['categories' => $categories],null);
        return response($payload, 200);
    }

    public function listSalesCategories(Request $request){
        $categories = $this->categoryService->listSalesCategories(15);
        $payload = GetResponseHelper::getSuccessResponse(['categories' => $categories],null);
        return response($payload, 200);
    }

    public function listNewArrivalsCategories(Request $request){
        $categories = $this->categoryService->listNewArrivalsCategories(15);
        $payload = GetResponseHelper::getSuccessResponse(['categories' => $categories],null);
        return response($payload, 200);
    }
}
