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

 
    public function listOrders(Request $request){
        $page = $request->input("page");
        $limit = $request->input("limit");
        $sort_by = $request->input("sort-by")?$request->input("sort-by"):"created_at-DESC";
        $search = $request->input("q");

        $user = $request->user();
        $orders = $user->orders()->where("canceled_at" , null);

        // search if q is provided 
        if ($search){
            $search = str_replace("-"," ", $search);
            $orders = $orders->select("orders.*")
              ->join("order_details", "order_details.order_id", "=","orders.id")
              ->join("products", "order_details.product_id","=",'products.id')
              ->where("products.name" , 'like' ,"%$search%");
        }
        $orders_total_count =$orders->count();
        $sorted_orders = ProductController::sortCollection($orders , $sort_by);
        $limited_sorted_orders = ProductController::filterNumber($sorted_orders,$page,$limit); 
        $result = $limited_sorted_orders->get();
        $orders_count_after_limit=  $result->count();

        $response_body = [
            'orders' => OrderResource::collection($result),
            'total_count' =>$orders_total_count,
            'count'=>$orders_count_after_limit
        ];
        return response($response_body,200);
    }
}
