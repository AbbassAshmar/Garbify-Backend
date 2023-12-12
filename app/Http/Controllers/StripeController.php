<?php

namespace App\Http\Controllers;

use App\Models\Product;
use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Request;
use Stripe\Exception\CardException;
use Stripe\StripeClient;
use Stripe\Webhook;
use App\Models\Color;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ShippingAddress;
use App\Models\ShippingMethod;
use App\Models\Size;
use UnexpectedValueException;
use Stripe\Exception\SignatureVerificationException;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Exception;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;

class StripeController extends Controller
{
    function stripeWebhookEventListener(Request $request){
        $stripe = new StripeClient(env("STRIPE_SECRET"));

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
            $response_body = HelperController::getFailedResponse($error, null);
            return response($response_body, 400); 
        } catch(SignatureVerificationException $e) {
            // Invalid signature
            $error = [
                'message'=>'Invalid signature.',
                'details' => $e->getMessage(),
                'code' => 400, 
            ];
            $response_body = HelperController::getFailedResponse($error, null);
            return response($response_body, 400); 
        }

        if ($event->type == "checkout.session.completed"){
            // get the session from the event 
            $session = $event->data->object;
            
            //retrieve the session with extra data : line_items
            try{
                $line_items = $stripe->checkout->sessions->retrieve(
                    $session->id,
                    ['expand' => ['line_items','line_items.data.price.product']]
                );
            }catch(ApiErrorException $e){
                $error = [
                    'message'=>'An unexpected error occurd.',
                    'details' => $e->getMessage(),
                    'code' => 200, 
                ];
                $response_body = HelperController::getFailedResponse($error, null);
                return response($response_body, 200); 
            }

            // Handle the event
            $address_details = $session['customer_details']['address'];
            $recipient_name =  $session['customer_details']['name'];
            $recipient_phone_number = $session['customer_details']['phone'];
            $recipient_email = $session['customer_details']['email'];

            $products_cost = $session['amount_subtotal'];
            $total_cost = $session['amount_total'];
            $tax_cost =  $session['total_details']['amount_tax'];
            $shipping_cost = $session['total_details']['amount_shipping'];
            $tax_percentage =($tax_cost * 100 ) / $total_cost ;

            $user_id = $session['metadata']['user_id'];
            $products = $line_items['line_items']['data'];
            $now = (new DateTime())->format('Y-m-d H:i:s');

            // create a shipping_address instance 
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

            // create an order instance 
            $order = Order::create([
                'created_at' => $now,
                'status' =>'Paid',
                'amount_total' => $total_cost,
                'amount_tax'=>$tax_cost,
                'amount_subtotal'=>$products_cost,
                'user_id'=>$user_id,
                'shipping_address_id' =>$shipping_address->id,
                'shipping_method_id' =>ShippingMethod::where("cost", $shipping_cost)->first()->id,
                'payment_intent_id' => $session->payment_intent, //used to refund if possible
                'percentage_tax' => $tax_percentage
            ]);

            // create order_detail instances for each product required by user 
            foreach($products as $product){
                $amount_discount = 0;
                $sale_percentage = $product['price']['product']['metadata']['sale_percentage'];
                if ($sale_percentage) {
                    $amount_discount =((int)$sale_percentage / 100) * $product['amount_subtotal']/100;
                }

                OrderDetail::create([
                    'canceled_at' => null,
                    'created_at' =>$now,
                    'order_id' =>$order->id ,
                    'product_id'=>(int)$product['price']['product']['metadata']['id'],
                    'ordered_quantity' =>$product['quantity'],
                    'color_id'=>Color::where('color',$product['price']['product']['metadata']['color'])->first()->id,
                    'size_id'=> Size::where("size",$product['price']['product']['metadata']['size'])->first()->id,
                    'amount_total'=>$product['amount_total']/ 100,
                    'amount_tax'=> $product['amount_tax'] / 100, 
                    'amount_subtotal'=> $product['amount_subtotal']/100,
                    'amount_unit' => $product['price']['unit_amount'] / 100,
                    'amount_discount' => $amount_discount,
                    'sale_id' => (int)$product['price']['product']['metadata']['sale_id'],
                ]);
            }
        }

        $data = ['action'=>'Received event',];
        $response_body = HelperController::getSuccessResponse($data, null);
        return response($response_body, 200); 
    }

    private function cancelEntireOrder(Order $order){
        $stripe = new StripeClient(env("STRIPE_SECRET"));
        try{
            // create a refund 
            $refund = $stripe->refunds->create([
                'payment_intent' => $order->payment_intent_id,
            ]);
        
            if ($refund->status === 'failed') {
                $error = [
                    'message'=>'Cancelation failed.',
                    'details' => $refund->failure_reason,
                    'code' => 400, 
                ];
                $response_body = HelperController::getFailedResponse($error, null);
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
            $response_body = HelperController::getSuccessResponse($data, $metaData);
            return response($response_body, 200);   

        } catch (ApiErrorException $e) {
            $error = [
                'message'=>'Cancelation failed.',
                'details' => $e->getMessage(),
                'code' => 400, 
            ];
            $response_body = HelperController::getFailedResponse($error, null);
            return response($response_body, 400);
        }
    }

    function cancelOrder(Request $request){
        $order  = Order::find($request->input("order_id"));
        HelperController::checkIfNotFound($order,"Order");

        // check if order has already been cancleled
        if ($order->status == 'Canceled'){
            $error = ['message' => 'Order has already been canceled.'];
            $response_body = HelperController::getFailedResponse($error,null);
            return response($response_body, 400);
        }

        //check order status 
        if (!$order->can_be_canceled){
            $error = ['message' => 'Order cancelation period is over.'];
            $response_body = HelperController::getFailedResponse($error,null);
            return response($response_body, 400);
        }

        // refund order
        return $this->cancelEntireOrder($order);
    }  

    function cancelProduct(Request $request){
        $order  = Order::find($request->input("order_id"));
        HelperController::checkIfNotFound($order,"Order");

        $product_id = $request->input("product_id");

        // check if product is part of the order
        $product_in_order = $order->orderDetails()->where('product_id' , $product_id)->first();
        HelperController::checkIfNotFound($product_in_order,'Product');

        // check if product has already been canceled 
        if ($product_in_order->canceled_at){
            $error = ['message' => 'Product has already been canceled.'];
            $response_body = HelperController::getFailedResponse($error,null);
            return response($response_body, 400);
        }

        //check order status 
        if (!$order->can_be_canceled){
            $error = ['message' => 'Order cancelation period is over.'];
            $response_body = HelperController::getFailedResponse($error,null);
            return response($response_body, 400);
        }

        // check if product to be canceled is the last in the order to cancel the whole order 
        if ($order->number_of_uncanceled_products == 1){
            return $this->cancelEntireOrder($order);
        }

        // refund product
        $stripe = new StripeClient(env("STRIPE_SECRET"));
        try {
            // create a refund 
            $refund = $stripe->refunds->create([
                'payment_intent' => $order->payment_intent_id,
                'amount' => $product_in_order->amount_total * 100
            ]);
        
            if ($refund->status === 'failed') {
                $error = [
                    'message'=>'Cancelation failed.',
                    'details' => $refund->failure_reason,
                    'code' => 400, 
                ];
                $response_body = HelperController::getFailedResponse($error, null);
                return response($response_body, 400);
            }

            $order->update(['status'=>'Partially canceled']);
            $product_in_order->update(['canceled_at' , Carbon::now()]);

            $data = ['action' => 'partially canceled'];
            $metaData = [
                'amount_refunded' => $refund->amount,
                "canceled_product"  => $product_in_order->product->name
            ];
            $response_body = HelperController::getSuccessResponse($data, $metaData);
            return response($response_body, 200);

        } catch (ApiErrorException $e) {
            $error = [
                'message'=>'Cancelation failed.',
                'details' => $e->getMessage(),
                'code' => 400, 
            ];
            $response_body = HelperController::getFailedResponse($error, null);
            return response($response_body, 400);
        }
    }
    

    function stripeBase(Request $request){
        $user = $request->user();
        $products = $request->input("products");
        $products =[
            [
                'product_id'=> 1,
                'color'=> 'red',
                'quantity'=> 3,
                'size'=> 'M 5.5 / W 2.5',
            ],
            [
                'product_id'=> 2,
                'color'=> 'blue',
                'quantity'=> 3,
                'size'=> 'M 6.5 / W 3.5',
            ]
        ];
        $metaData = $products;

        $shipping_options = [
            [
                'id'=>1,
                "name"=>"Free shipping",
                "cost" =>0,
                "min_days"=>6,
                'max_days' =>8
            ],
            [
                'id'=>2,
                "name"=>"15$ shipping",
                "cost" =>15,
                "min_days"=>4,
                'max_days' =>6
            ]
        ];

        // include total_price (price of each product * quantity)
        $products = array_map(function($product){

            $get_product = Product::find($product['product_id']);
            HelperController::checkIfNotFound($get_product, "Product"); // check if product to be ordered exists

            $get_color = $get_product->colors()->where('color', $product['color'])->first();
            $color_error = "Color ".$product['color']. ' of '.$get_product->name;
            HelperController::checkIfNotFound($get_color, $color_error);// check if color to be ordered exists

            $get_size = $get_product->sizes()->where('size', $product['size'])->first();
            $size_error = "Size ".$product['size']. ' of '.$get_product->name;
            HelperController::checkIfNotFound($get_size, "Size ".$size_error);// check if size to be ordered exists

            $product['product_object'] = $get_product;

            $image =  $get_product->images()->where('color_id',$get_color->id)->first();
            if(!$image) $image = $get_product->thumbnail;
            $product['product_image']=$image->image_url;

            //testing
            $product['product_image']= ['https://cdn.thewirecutter.com/wp-content/media/2023/05/running-shoes-2048px-9718.jpg'];

            return $product;
        },$products);
        
        $stripe_key = env("STRIPE_SECRET");
        // $shipping_options = ShippingMethod::all()->all();
        try{
            $stripe  = new StripeClient($stripe_key);
            $taxes = [
                'tax1' => $stripe->taxRates->create([
                    'display_name' => 'Sales Tax',
                    'inclusive' => false,
                    'percentage' => 11,
                    'country' => 'SE',
                    'description' => 'SE Sales Tax',
                ]),
                'tax2' =>$stripe->taxRates->create([
                    'display_name' => 'Sales Tax',
                    'inclusive' => false,
                    'percentage' => 22,
                    'country' => 'DK',
                    'description' => 'DK Sales Tax',
                ])  
            ];
            $session = $stripe->checkout->sessions->create([
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
                                    'sale_percentage'=>$product['product_object']->current_sale? ($product['product_object']->current_sale->sale_percentage)  :null,
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

                'success_url' => 'http://localhost:5173/checkout-successful',
                'cancel_url' => 'http://localhost:5173/cart',

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
            $response_body = HelperController::getSuccessResponse($data, null);
            return response($response_body,200);
        }catch (ApiErrorException $e){
            $error = [
                'message'=>'Cancelation failed.',
                'details' => $e->getMessage(),
                'code' => 500, 
            ];
            $response_body = HelperController::getFailedResponse($error, null);
            return response($response_body, 500);
        }
    }
}
