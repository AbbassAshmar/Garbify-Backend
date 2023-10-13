<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ShippingAddress;
use App\Models\ShippingMethod;
use App\Models\Size;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
class OrderTest extends TestCase
{
    use RefreshDatabase;

    private $user_1;
    private $user_2;
    private $token_1;
    private $token_2;

    private $product_1;
    private $product_2;

    private $order_1;
    private $order_4;
    private $order_3;
    private $order_5;

    private $order_detail_1;
    private $order_detail_2;
    private $shipping_method;
    private $shipping_address;
    
    // runs before each test
    public function setUp():void
    {
        parent::setUp();

        // create users and a token for the first user
        $users_tokens = HelperTest::create_users();
        $this->user_1 = $users_tokens['users'][0];
        $this->user_2 = $users_tokens['users'][1];
        $this->token_1 = $users_tokens['tokens'][0];
        $this->token_2 = $users_tokens['tokens'][1];

        $size_1 = HelperTest::create_sizes()[0];
        $color_1 = HelperTest::create_colors()[0];
        $products = HelperTest::create_products();
        $this->product_1 = $products[0];
        $this->product_2 = $products[1];
        $product_3 = $products[2];

        // create shipping method 
        $shipping_method = ShippingMethod::create([
            'name' => '15$ shipping',
            'cost' =>15,
            'min_days' =>4,
            'max_days' =>7
        ]);

        //create shipping address 
        $shipping_address = ShippingAddress::create([
            'user_id' =>$this->user_1->id,
            'country' =>"US",
            "city" =>"new york city",
            "state" =>"new york state",
            "postal_code" =>"10001",
            'address_line_1' => "47 W 13th St Building 14 floor 2",
            'address_line_2' => "New York, NY 10011, USA",
            'recipient_name' => "jack smith",
            "phone_number" =>"00923842873489",
            "email" =>"jackSmith123@gmail.com"
        ]);
        $shipping_address_2 = ShippingAddress::create([
            'user_id' =>$this->user_2->id,
            'country' =>"US",
            "city" =>"miami",
            "state" =>"florida",
            "postal_code" =>"33101",
            'address_line_1' => "123 Ocean Drive, Building C, Apt 4B",
            'address_line_2' => "Miami, FL 33101, USA",
            'recipient_name' => "taylor smith",
            "phone_number" =>"009382392",
            "email" =>"taylor@gmail.com"
        ]);
        
        // create orders   
        //user not canceled order      
        $order_1 = Order::create(["status"=>"paid",
            "total_cost"=>300,
            "tax_cost"=>30,
            "products_cost"=>255,
            "user_id"=>$this->user_1->id,
            "shipping_address_id"=>$shipping_address->id,
            "shipping_method_id"=>$shipping_method->id,
        ]);
        //user canceled order
        $order_2 = Order::create([
            "status"=>"cancelled",
            'canceled_at' =>Carbon::now()->addDays(1),
            "total_cost"=>145,
            "tax_cost"=>30,
            "products_cost"=>100,
            "user_id"=>$this->user_1->id,
            "shipping_address_id"=>$shipping_address->id,
            "shipping_method_id"=>$shipping_method->id,
        ]);
        // user_2 order
        $order_3 = Order::create([
            'created_at' =>(new Carbon("2019-09-05")),
            "status"=>"paid",
            "total_cost"=>100,
            "tax_cost"=>30,
            "products_cost"=>55,
            "user_id"=>$this->user_2->id,
            "shipping_address_id"=>$shipping_address_2->id,
            "shipping_method_id"=>$shipping_method->id,
        ]);
        //user_2 second order
        $order_4 = Order::create([
            'created_at' =>(new Carbon("2022-09-05")),
            "status"=>"paid",
            "total_cost"=>165,
            "tax_cost"=>40,
            "products_cost"=>110,
            "user_id"=>$this->user_2->id,
            "shipping_address_id"=>$shipping_address_2->id,
            "shipping_method_id"=>$shipping_method->id,
        ]);
        //user_2 third order
        $order_5 = Order::create([
            'created_at' =>(new Carbon("2023-09-05")),
            "status"=>"paid",
            "total_cost"=>175,
            "tax_cost"=>40,
            "products_cost"=>110,
            "user_id"=>$this->user_2->id,
            "shipping_address_id"=>$shipping_address_2->id,
            "shipping_method_id"=>$shipping_method->id,
        ]);
        //create order_details for each order (represent products)
        $order_detail_1 = OrderDetail::create([
            "order_id"=>$order_1->id,
            "product_id"=>$this->product_1->id,
            "ordered_quantity" => 2,
            "color_id"=>$color_1->id,
            "size_id" =>$size_1->id,
            'product_total_price' => 200
        ]);
        $order_detail_2 = OrderDetail::create([
            "order_id"=>$order_1->id,
            "product_id"=>$this->product_2->id,
            "ordered_quantity" => 1,
            "color_id"=>$color_1->id,
            "size_id" =>$size_1->id,
            'product_total_price' => 55
        ]);
        $order_detail_3 = OrderDetail::create([
            "order_id"=>$order_2->id,
            "product_id"=>$this->product_2->id,
            "ordered_quantity" => 1,
            "color_id"=>$color_1->id,
            "size_id" =>$size_1->id,
            'product_total_price' => 55
        ]);
        $order_detail_4 = OrderDetail::create([
            "order_id"=>$order_3->id,
            "product_id"=>$this->product_1->id,
            "ordered_quantity" => 1,
            "color_id"=>$color_1->id,
            "size_id" =>$size_1->id,
            'product_total_price' => 100
        ]);
        $order_detail_5 = OrderDetail::create([
            "order_id"=>$order_4->id,
            "product_id"=>$this->product_2->id,
            "ordered_quantity" => 2,
            "color_id"=>$color_1->id,
            "size_id" =>$size_1->id,
            'product_total_price' => 110
        ]);
        $order_detail_6= OrderDetail::create([
            "order_id"=>$order_5->id,
            "product_id"=>$product_3->id,
            "ordered_quantity" => 1,
            "color_id"=>$color_1->id,
            "size_id" =>$size_1->id,
            'product_total_price' => 55
        ]);
        $this->order_1 = $order_1;
        $this->order_4 =$order_4;
        $this->order_3 = $order_3;
        $this->order_5 = $order_5;

        $this->order_detail_1 = $order_detail_1;
        $this->order_detail_2 = $order_detail_2;
        $this->shipping_address = $shipping_address;
        $this->shipping_method = $shipping_method;
    }


    public function test_list_orders(): void
    {
        $headers = ["Authorization" => "Bearer " . $this->token_1];
        $request = $this->getJson("/api/orders",$headers);
        $request->assertOk();
        $request->assertJson([
            'orders'=>[
                [
                    "id"=>$this->order_1->id,
                    "created_at"=>$this->order_1->created_at->jsonSerialize(),
                    "status"=>$this->order_1->status,
                    "total_cost"=>$this->order_1->total_cost,
                    "shipping_status"=>$this->order_1->shipping_status,
                    "return_cancellation_info" =>$this->order_1->return_cancellation_info,
                    "recipient_name"=>$this->order_1->shippingAddress->recipient_name,
                    "products"=>[
                        [
                            "id"=>$this->product_1->id,
                            "thumbnail"=>null,
                            "name"=>$this->product_1->name,
                            "ordered_quantity"=>$this->order_detail_1->ordered_quantity,
                        ],
                        [ 
                            "id"=> $this->product_2->id,
                            "thumbnail"=>null,
                            "name"=>$this->product_2->name,
                            "ordered_quantity"=>$this->order_detail_2->ordered_quantity,
                        ]
                    ]
                ]
            ],
            'total_count' =>1,
            "count" => 1
        ]);
    }

    public function test_list_orders_unauthenticated_user()
    {
        $request = $this->getJson("/api/orders");
        $request->assertUnauthorized();
        $request->assertJson(["message"=>"Unauthenticated."]);
    }

    //handle limit 
    public function test_list_orders_limited()
    {
        $headers = ["Authorization" => "Bearer " . $this->token_2];
        $request = $this->getJson("/api/orders?page=1&limit=1",$headers);
        $request->assertOk();
        $request->assertJson([
            "count"=> 1,
            'total_count' =>3
        ]);
    }

    // handle sort by 
    public function test_list_orders_sort_by_total_cost_DESC()
    {        
        $headers = ["Authorization" => "Bearer " . $this->token_2];
        $request = $this->getJson("/api/orders?sort-by=total_cost-DESC",$headers);
        $request->assertOk();
        $request->assertJson([
            'total_count' =>3,
            "count"=> 3,
            "orders" =>[
                ["id"=>$this->order_5->id],
                ["id"=>$this->order_4->id],
                ["id"=>$this->order_3->id]
            ]
        ]);
    }

    public function test_list_orders_sort_by_total_cost_ASC()
    {
        $headers = ["Authorization" => "Bearer " . $this->token_2];
        $request = $this->getJson("/api/orders?sort+by=total_cost+ASC",$headers);
        $request->assertOk();
        $request->assertJson([
            'total_count' =>3,
            "count"=> 3,
            "orders" =>[
                ["id"=>$this->order_3->id],
                ["id"=>$this->order_4->id],
                ['id' =>$this->order_5->id]
            ]
        ]);
    }
    
    public function test_list_orders_sort_by_created_at_DESC()
    {
        $headers = ["Authorization" => "Bearer " . $this->token_2];
        $request = $this->getJson("/api/orders?sort+by=created_at+DESC",$headers);
        $request->assertOk();
        $request->assertJson([
            "orders" =>[
                ["id"=>$this->order_5->id],
                ["id"=>$this->order_4->id],
                ["id"=>$this->order_3->id]
            ],
            'total_count' =>3,
            "count"=> 3
        ]);
    }
    
    public function test_list_orders_sort_by_created_at_ASC()
    {
        $headers = ["Authorization" => "Bearer " . $this->token_2];
        $request = $this->getJson("/api/orders?sort+by=created_at+ASC",$headers);
        $request->assertOk();
        $request->assertJson([
            "orders" =>[
                ["id"=>$this->order_3->id],
                ["id"=>$this->order_4->id],
                ['id'=>$this->order_5->id],
            ],
            'total_count' =>3,
            "count"=> 3,
        ]);
    }

    // handle search 
    public function test_list_orders_search()
    {
        $headers = ["Authorization" => "Bearer " . $this->token_2];
        $request = $this->getJson("/api/orders?q=air+force",$headers);
        $request->assertOk();

        //product of Order_3 is "air force",product of order_5 is "air force 2)
        $request->assertJson([
            "orders" =>[
                ['id'=>$this->order_5->id],
                ["id"=>$this->order_3->id]
            ],
            'total_count' =>2,
            "count"=> 2
        ]);
    }
}
