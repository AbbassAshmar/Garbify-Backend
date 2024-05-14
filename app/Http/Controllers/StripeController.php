<?php

namespace App\Http\Controllers;

use App\Exceptions\ProductOutOfStockException;
use App\Helpers\GetResponseHelper;
use App\Helpers\ValidateResourceHelper;
use App\Models\Product;
use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Request;
use Stripe\Exception\CardException;
use Stripe\StripeClient;
use Stripe\Webhook;
use App\Models\Color;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Sale;
use App\Models\ShippingAddress;
use App\Models\ShippingMethod;
use App\Models\Size;
use UnexpectedValueException;
use Stripe\Exception\SignatureVerificationException;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;

class StripeController extends Controller
{
    private $stripe;

    // instantiated at AppServiceProvider 
    function __construct(StripeClient $stripe){
        $this->$stripe = $stripe;
    }
    
    protected function createShippingAddress($session,$user_id){
        $address_details = $session['customer_details']['address'];
        $recipient_name =  $session['customer_details']['name'];
        $recipient_phone_number = $session['customer_details']['phone'];
        $recipient_email = $session['customer_details']['email'];
        $now = (new DateTime())->format('Y-m-d H:i:s');

        $shipping_address = ShippingAddress::create([
            'user_id' => $user_id,
            'country' =>$address_details['country'],
            'city' =>$address_details['city'],
            'state'=>$address_details['state'],
            'address_line_1'=>$address_details['line1'],
            'address_line_2'=>$address_details['line2'],
            'postal_code'=>$address_details['postal_code'],
            'recipient_name'=>$recipient_name,
            'email'=>$recipient_email,
            'phone_number' =>$recipient_phone_number,
            'name'=>$recipient_name,
            'created_at' => $now,
        ]);

        return $shipping_address;
    }

    protected function createOrder($session,$shipping_address_id,$user_id){
        $amount_subtotal = $session['amount_subtotal'];
        $amount_total = $session['amount_total'];
        $amount_tax =  $session['total_details']['amount_tax'];

        $shipping_cost = $session['total_details']['amount_shipping'];
        $tax_percentage =($amount_tax * 100 ) / $amount_total ; 
        $now = (new DateTime())->format('Y-m-d H:i:s');

        $order = Order::create([
            'payment_intent_id' => $session['payment_intent'], //used to refund if possible
            'created_at' => $now,
            'status' =>'Paid',
            'amount_total' => $amount_total,
            'amount_tax'=>$amount_tax,
            'amount_subtotal'=>$amount_subtotal,
            'user_id'=>$user_id,
            'shipping_address_id' =>$shipping_address_id,
            'shipping_method_id' =>ShippingMethod::where("cost", $shipping_cost)->first()->id,
            'percentage_tax' => $tax_percentage
        ]);

        return $order;
    }

    protected function createOrderDetails($products , $order_id){
        $now = (new DateTime())->format('Y-m-d H:i:s');

        // create order_detail instances for each product required by user 
        foreach($products as $product){
            $product_id = (int)$product['price']['product']['metadata']['id'];
            $product_obj = Product::find($product_id);
            ValidateResourceHelper::ensureResourceExists($product_obj,"Product");

            //update product quantity 
            $product_obj->update(['quantity' => $product_obj->quantity - $product['quantity']]);

            //update sale quantity
            $amount_discount = 0;
            $sale_id = $product['price']['product']['metadata']['sale_id'];
            if ($sale_id){
                $sale_obj = Sale::find((int)$sale_id);
                ValidateResourceHelper::ensureResourceExists($sale_id, "Sale");
                $sale_percentage = $sale_obj->sale_percentage;
                $amount_discount =($sale_percentage / 100) * $product['amount_subtotal']/100;
                $sale_obj->update(['quantity' => $sale_obj->quantity -  $product['quantity']]);
            }

            OrderDetail::create([
                'canceled_at' => null,
                'created_at' =>$now,
                'order_id' =>$order_id ,
                'product_id'=>$product_obj->id,
                'ordered_quantity' =>$product['quantity'],
                'color_id'=>Color::where('color',$product['price']['product']['metadata']['color'])->first()->id,
                'size_id'=> Size::where("size",$product['price']['product']['metadata']['size'])->first()->id,
                'amount_total'=>$product['amount_total']/ 100,
                'amount_tax'=> $product['amount_tax'] / 100, 
                'amount_subtotal'=> $product['amount_subtotal']/100,
                'amount_unit' => $product['price']['unit_amount'] / 100,
                'amount_discount' => $amount_discount,
                'sale_id' => $sale_id,
            ]);
        }
    }

    function stripeWebhookEventListener(Request $request){
        // This is your Stripe CLI webhook secret for testing your endpoint locally.
        $endpoint_secret = env("WEBHOOK_SECRET");
        $sig_header = $request->header("Stripe-Signature");
        $payload = $request->getContent();
        $event = null;

        try {
            // sig_header : payload hashed by stripe using endpoint secret
            // rehash payload using own secret, if equals sig_header, then accept it (means owr secret equals their secret)
            $event = Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(UnexpectedValueException $e) {
            // Invalid payload
            $error = [
                'message'=>'Invalid payload.',
                'details' => $e->getMessage(),
                'code' => 400, 
            ];
            $response_body = GetResponseHelper::getFailedResponse($error, null);
            return response($response_body, 400); 
        } catch(SignatureVerificationException $e) {
            // Invalid signature
            $error = [
                'message'=>'Invalid signature.',
                'details' => $e->getMessage(),
                'code' => 400, 
            ];
            $response_body = GetResponseHelper::getFailedResponse($error, null);
            return response($response_body, 400); 
        }

        if ($event->type == "checkout.session.completed"){
            // get the session from the event 
            $session = $event->data->object;

            //retrieve the session with extra data : line_items
            $line_items = $this->stripe->checkout->sessions->retrieve(
                $session->id,
                ['expand' => ['line_items','line_items.data.price.product']]
            );

            // Handle the event
            $user_id = $session['metadata']['user_id'];
            $products = $line_items['line_items']['data'];

            try {
                DB::beginTransaction();

                $shipping_address = $this->createShippingAddress($session,$user_id);
                $order = $this->createOrder($session,$shipping_address->id,$user_id);
                $this->createOrderDetails($products,$order->id);

                DB::commit();
            }catch(Exception $e){
                DB::rollBack();
                $error = [
                    'message'=>'An unexpected error occurd.',
                    'details' => $e->getMessage(),
                    'code' => 200, 
                ];
                $response_body = GetResponseHelper::getFailedResponse($error, null);
                return response($response_body, 200); 
            }
        }
        
        $data = ['action'=>'Received event',];
        $response_body = GetResponseHelper::getSuccessResponse($data, null);
        return response($response_body, 200); 
    }

    private function cancelEntireOrder(Order $order){
        try{
            // create a refund 
            $refund = $this->stripe->refunds->create([
                'payment_intent' => $order->payment_intent_id,
            ]);
        
            if ($refund->status === 'failed') {
                $error = [
                    'message'=>'Cancelation failed.',
                    'details' => $refund->failure_reason,
                    'code' => 400, 
                ];
                $response_body = GetResponseHelper::getFailedResponse($error, null);
                return response($response_body, 400);
            }

            // update canceled_at of order and each order detail to now
            $now = Carbon::now();
            $order->update(['status'=>'Canceled','canceled_at' => $now]);
            foreach($order->orderDetails()->where("canceled_at", null)->get() as $ordered_product){
                $ordered_product->update(['canceled_at' =>  $now]);
            }

            $data = ['action' => 'canceled'];
            $metaData = [
                'amount_refunded' => $refund->amount,
            ];
            $response_body = GetResponseHelper::getSuccessResponse($data, $metaData);
            return response($response_body, 200);   

        } catch (ApiErrorException $e) {
            $error = [
                'message'=>'Cancelation failed.',
                'details' => $e->getMessage(),
                'code' => 400, 
            ];
            $response_body = GetResponseHelper::getFailedResponse($error, null);
            return response($response_body, 400);
        }
    }

    // Partially cancel Order
    function cancelOrder(Request $request){
        $order  = Order::find($request->input("order_id"));
        ValidateResourceHelper::ensureResourceExists($order,"Order");

        // check if order has already been cancleled
        if ($order->status == 'Canceled'){
            $error = ['message' => 'Order has already been canceled.'];
            $response_body = GetResponseHelper::getFailedResponse($error,null);
            return response($response_body, 400);
        }

        //check order status 
        if (!$order->can_be_canceled){
            $error = ['message' => 'Order cancelation period is over.'];
            $response_body = GetResponseHelper::getFailedResponse($error,null);
            return response($response_body, 400);
        }

        // refund order
        return $this->cancelEntireOrder($order);
    }  


    // cancel order partially
    function cancelProduct(Request $request){
        $order  = Order::find($request->input("order_id"));
        ValidateResourceHelper::ensureResourceExists($order,"Order");

        $product_id = $request->input("product_id");

        // check if product is part of the order
        $product_in_order = $order->orderDetails()->where('product_id' , $product_id)->first();
        ValidateResourceHelper::ensureResourceExists($product_in_order,'Product');

        // check if product has already been canceled 
        if ($product_in_order->canceled_at){
            $error = ['message' => 'Product has already been canceled.'];
            $response_body = GetResponseHelper::getFailedResponse($error,null);
            return response($response_body, 400);
        }

        //check order status 
        if (!$order->can_be_canceled){
            $error = ['message' => 'Order cancelation period is over.'];
            $response_body = GetResponseHelper::getFailedResponse($error,null);
            return response($response_body, 400);
        }

        // check if product to be canceled is the last in the order to cancel the whole order 
        if ($order->number_of_uncanceled_products == 1){
            return $this->cancelEntireOrder($order);
        }

        // refund product
        try {
            $refund = $this->stripe->refunds->create([
                'payment_intent' => $order->payment_intent_id,
                'amount' => $product_in_order->amount_total * 100
            ]);
        
            if ($refund->status === 'failed') {
                $error = [
                    'message'=>'Cancelation failed.',
                    'details' => $refund->failure_reason,
                    'code' => 400, 
                ];
                $response_body = GetResponseHelper::getFailedResponse($error, null);
                return response($response_body, 400);
            }

            $order->update(['status'=>'Partially canceled']);
            $product_in_order->update(['canceled_at' , Carbon::now()]);

            $data = ['action' => 'partially canceled'];
            $metaData = [
                'amount_refunded' => $refund->amount,
                "canceled_product"  => $product_in_order->product->name
            ];
            $response_body = GetResponseHelper::getSuccessResponse($data, $metaData);
            return response($response_body, 200);

        } catch (ApiErrorException $e) {
            $error = [
                'message'=>'Cancelation failed.',
                'details' => $e->getMessage(),
                'code' => 400, 
            ];
            $response_body = GetResponseHelper::getFailedResponse($error, null);
            return response($response_body, 400);
        }
    }

    //used to checkout a product or more
    function checkoutProducts(Request $request){
        $user = $request->user();
        $products = $request->input("products");
        $shipping_options = ShippingMethod::all()->all();

        $products = array_map(function($product){
            $get_product = Product::find($product['product_id']);
            ValidateResourceHelper::ensureResourceExists($get_product, "Product"); // check if product to be ordered exists

            $get_color = $get_product->colors()->where('color', $product['color'])->first();
            $color_error = "Color ".$product['color']. ' of '.$get_product->name;
            ValidateResourceHelper::ensureResourceExists($get_color, $color_error);// check if color to be ordered exists

            $get_size = $get_product->sizes()->where('size', $product['size'])->first();
            $size_error = "Size ".$product['size']. ' of '.$get_product->name;
            ValidateResourceHelper::ensureResourceExists($get_size, "Size ".$size_error);// check if size to be ordered exists

            $new_quantity = $get_product-> quantity - $product['quantity']; // check for sufficient quantity
            if ($new_quantity < 0) {
                throw ProductOutOfStockException::insufficientStock($get_product->name);
            }

            // check for sale quantity if present // user can order max the sale quantity
            if ($get_product->current_sale && !$get_product->current_sale->checkIfQuantitySufficient($product['quantity'])){
                throw ProductOutOfStockException::insufficientStock($get_product->name);
            }

            $product['product_object'] = $get_product;

            $image =  $get_product->images()->where('color_id',$get_color->id)->first();
            if(!$image) $image = $get_product->thumbnail;
            $product['product_image']=$image->image_url;

            return $product;
        },$products);
        
        try{
            $taxes = [
                'tax1' => $this->stripe->taxRates->create([
                    'display_name' => 'Sales Tax',
                    'inclusive' => false,
                    'percentage' => 11,
                    'country' => 'SE',
                    'description' => 'SE Sales Tax',
                ]),
                'tax2' =>$this->stripe->taxRates->create([
                    'display_name' => 'Sales Tax',
                    'inclusive' => false,
                    'percentage' => 22,
                    'country' => 'DK',
                    'description' => 'DK Sales Tax',
                ])  
            ];
            $session = $this->stripe->checkout->sessions->create([
                'payment_method_types'=>['card'],
                'line_items' => array_map(function($product) use($taxes){
                    return [
                        'dynamic_tax_rates' => [
                            $taxes['tax1']->id,
                            $taxes['tax2']->id,
                        ],
                        'price_data' => [
                            'currency' =>'usd',
                            'product_data' =>[ 
                                'name' => $product['product_object']->name,
                                'images'=>$product['product_image'],
                                'metadata'=>[ // additional info about the product
                                    'id' =>$product['product_object']->id,
                                    'sale_id' => $product['product_object']->current_sale?$product['product_object']->current_sale->id:null,
                                    'sale_percentage'=>$product['product_object']->current_sale? ($product['product_object']->current_sale->sale_percentage):null,
                                    'color'=>$product['color'],
                                    "size"=>$product['size']
                                ],
                            ],
                            'unit_amount' => $product['product_object']->current_price*100,
                        ],
                        'quantity'=> $product['quantity']
                    ];
                },$products),
            
                'mode' => 'payment',

                'success_url' => 'http://localhost:5173/products',
                'cancel_url' => 'http://localhost:5173/shopping_cart',

                'metadata' => [  // stored in the payment intent to be retrieved later 
                    'user_id'=>$user->id,
                ],

                'phone_number_collection' => [
                    'enabled' => true,
                ],

                // shipping 
                'shipping_address_collection' => ['allowed_countries' => ['SE', 'DK']],
                'shipping_options' => array_map(function($method){
                    return [
                        'shipping_rate_data' => [
                            'type' =>'fixed_amount',
                            'fixed_amount' => [
                            'amount' => $method['cost']*100,
                            'currency' => 'usd',
                            ],
                            'display_name' => $method['name'],
                            'delivery_estimate' => [
                                'minimum' => [
                                    'unit' => 'business_day',
                                    'value' => $method['min_days'],
                                ],
                                'maximum' => [
                                    'unit' => 'business_day',
                                    'value' => $method['max_days'],
                                ],
                            ],
                        ],
                    ];
                },$shipping_options),
            ]);

            $data = ['form_url'=>$session->url];
            $response_body = GetResponseHelper::getSuccessResponse($data, null);
            return response($response_body,201);
        }catch (ApiErrorException $e){
            $error = [
                'message'=>'Cancelation failed.',
                'details' => $e->getMessage(),
                'code' => 500, 
            ];
            $response_body = GetResponseHelper::getFailedResponse($error, null);
            return response($response_body, 500);
        }
    }
}
