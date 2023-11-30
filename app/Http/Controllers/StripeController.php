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
use DateTime;
class StripeController extends Controller
{
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
            return response(['error' => $e->getMessage()] , 400);
        } catch(SignatureVerificationException $e) {
            // Invalid signature
            return response(['error' => $e->getMessage()] , 400);
        }

        // Handle the event
        $address_details = $event['data']['object']['customer_details']['address'];
        $recipient_name =  $event['data']['object']['customer_details']['name'];
        $recipient_phone_number = $event['data']['object']['customer_details']['phone'];
        $recipient_email = $event['data']['object']['customer_details']['email'];

        $products_cost = $event['data']['object']['amount_subtotal'];
        $total_cost = $event['data']['object']['amount_total'];
        $tax_cost =  $event['data']['object']['total_details']['amount_tax'];
        $shipping_cost = $event['data']['object']['total_details']['amount_shipping'];

        $user_id = $event['data']['object']['metadata']['user_id'];
        $products = json_decode( $event['data']['object']['metadata']['products'],true);
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
            'status' =>'paid',
            'total_cost' => $total_cost,
            'tax_cost'=>$tax_cost,
            'products_cost'=>$products_cost,
            'user_id'=>$user_id,
            'shipping_address_id' =>$shipping_address->id,
            'shipping_method_id' =>ShippingMethod::where("cost", $shipping_cost)->first()->id
        ]);

        // create order_detail instances for each product required by user 
        foreach($products as $product){
            OrderDetail::create([
                'created_at' =>$now,
                'order_id' =>$order->id ,
                'product_id'=>$product['product_id'],
                'ordered_quantity' =>$product['quantity'],
                'color_id'=>Color::where('color',$product['color'])->first()->id,
                'size_id'=> Size::where("size",$product['size'])->first()->id,
                'product_total_price'=>$product['total_price']
            ]);
        }

        return response(['Received unknown event type'=>$event], 200);
    }
    
    function stripeBase(Request $request){
        $user = $request->user();
        // $products = $request->input("products");
        $products =[
            [
                'product_id'=> 2,
                'color'=> 'red',
                'quantity'=> 3,
                'size'=> 'M 2.5 / W 4',
            ],
            [
                'product_id'=> 3,
                'color'=> 'red',
                'quantity'=> 3,
                'size'=> 'M 2.5 / W 4',
            ]
        ];
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
            $product['total_price']= Product::find($product['product_id'])->current_price * $product['quantity'];
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
                    $product_object = Product::find($product['product_id']);
                    $product_color = Color::where("color",$product['color'])->first();
                    $product_image = $product_object->images()->where('color_id',$product_color->id)->first();
                    return [
                        'dynamic_tax_rates' => [
                            $taxes['tax1']->id,
                            $taxes['tax2']->id,
                        ],
                        'price_data' => [
                            'currency' =>'usd',
                            'product_data' =>[
                                'metadata'=>[
                                    'color'=>$product['color'],
                                    "size"=>$product['size']
                                ],
                                'name' => $product_object->name,
                                'images'=>['https://cdn.thewirecutter.com/wp-content/media/2023/05/running-shoes-2048px-9718.jpg']
                                // 'images=>[$product_image->url]
                            ],
                            'unit_amount' => $product_object->current_price*100,
                        ],
                        'quantity'=> $product['quantity']
                    ];
                },$products),
                'mode' => 'payment',

                'success_url' => 'http://localhost:5173/checkout-successful',
                'cancel_url' => 'http://localhost:5173/cart',

                'metadata' => [
                    'user_id'=>$user->id,
                    'token_id'=>$request->bearerToken(),
                    'products'=> json_encode($products),

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

            return response(['form_url'=>$session->url]);
        }catch (CardException $e){
            return response(["error"=>$e->getMessage()]);
        }
    }
}
