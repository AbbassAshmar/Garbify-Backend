<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

// add to order_details , sales field to check sales at ordering time
class OrderController extends Controller
{
    // listByToken
    public function listOrders(Request $request){
        $page = $request->input("page");
        $limit = $request->input("limit");
        $sort_by = $request->input("sort_by")?$request->input("sort_by"):"created_at DESC";
        $search = $request->input("q");

        // get orders of user
        $user = $request->user();
        $orders = $user->orders()->where("canceled_at" , null);

        // search by product name if q is provided 
        if ($search){
            $orders = $orders->select("orders.*")
              ->join("order_details", "order_details.order_id", "=","orders.id")
              ->join("products", "order_details.product_id","=",'products.id')
              ->where("products.name" , 'like' ,"%$search%");
        }

        $response = HelperController::getCollectionAndCount($orders,$sort_by,$page,$limit,OrderResource::class);
        
        return response($response,200);
    }
}
