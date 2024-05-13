<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

//first status
    //paid : after clients pays 
//second status 
    //Awaiting Shipment  : seller accepts the order and now it's awaiting to be picked up by the shipper 
    //declined : seller decided not to sell his item and cancelled order , money is returned to the customer
//third status 
    //shipping : order picked by shipper and on it's way to the customer
// fourth status 
    //completed: order has been picked by the customer from the shipper 

//on hold : some problem happend when shipping / or with seller and is being solved
//canceled : before shipping status (third), customer can cancel order and get his money back
//partially canceled : before shipping status (third) , customer can cancel one or more of the products and get his money back



// add to order_details , sales field to check sales at ordering time
class OrderController extends Controller
{
    // listByToken
    public function listOrders(Request $request){
        $pageLimt =['page'=> $request->input("page"),'limit'=>$request->input("limit")];
        $sort_by =$request->input("sort")?$request->input("sort"):"created_at DESC";
        $search = $request->input("q");

        // get orders of user
        $user = $request->user();
        $orders = $user->orders()->where("orders.canceled_at" , null);

        // search by product name if q is provided 
        if ($search){
            $orders = $orders->select("orders.*")
              ->join("order_details", "order_details.order_id", "=","orders.id")
              ->join("products", "order_details.product_id","=",'products.id')
              ->where("products.name" , 'like' ,"%$search%");
        }

        $response = HelperController::getCollectionAndCount(
            $orders,
            $sort_by,
            $pageLimt,
            OrderResource::class,
            "orders"
        );
        
        return response($response,200);
    }

    public function listCanceledOrders(Request $request){
        $pageLimt =['page'=> $request->input("page"),'limit'=>$request->input("limit")];
        $sort_by =$request->input("sort")?$request->input("sort"):"created_at DESC";
        $search = $request->input("q");

        // get orders of user
        $user = $request->user();
        $orders = $user->orders()->where('canceled_at', '!=' ,null);

        // search by product name if q is provided 
        if ($search){
            $orders = $orders->select("orders.*")
              ->join("order_details", "order_details.order_id", "=","orders.id")
              ->join("products", "order_details.product_id","=",'products.id')
              ->where("products.name" , 'like' ,"%$search%");
        }

        $response = HelperController::getCollectionAndCount(
            $orders,
            $sort_by,
            $pageLimt,
            OrderResource::class,
            "orders"
        );
        
        return response($response,200);
    }
}
