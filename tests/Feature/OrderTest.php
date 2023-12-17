<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ShippingAddress;
use App\Models\ShippingMethod;
use Database\Seeders\UserRolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use Tests\Feature\HelperTest;

class OrderTest extends TestCase
{
    // use RefreshDatabase;
    use DatabaseMigrations; // to reset all ids too
    // use DatabaseTransactions;

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
        $this->seed(UserRolePermissionSeeder::class);
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
        $order_1 = Order::create([
            "status"=>"Paid",
            'amount_total' =>300,
            'amount_tax'=>30,
            'amount_subtotal'=>255,
            "user_id"=>$this->user_1->id,
            "shipping_address_id"=>$shipping_address->id,
            "shipping_method_id"=>$shipping_method->id,
            'payment_intent_id' =>"jskldfjklswr3423",
            'percentage_tax' => 10
        ]);
        //user canceled order
        $order_2 = Order::create([
            "status"=>"Cancelled",
            'canceled_at' =>Carbon::now()->addDays(1),
            "total_cost"=>145,
            "tax_cost"=>30,
            "products_cost"=>100,
            "user_id"=>$this->user_1->id,
            "shipping_address_id"=>$shipping_address->id,
            "shipping_method_id"=>$shipping_method->id,
            'payment_intent_id' =>"32094eriwuri324",
            'amount_total' =>145,
            'amount_tax'=>30,
            'amount_subtotal'=>100,
            'percentage_tax' => 20,
        ]);

        // user_2 order
        $order_3 = Order::create([
            'created_at' =>(new Carbon("2019-09-05")),
            "status"=>"Paid",
            "user_id"=>$this->user_2->id,
            "shipping_address_id"=>$shipping_address_2->id,
            "shipping_method_id"=>$shipping_method->id,
            "amount_total"=>100,
            "amount_tax"=>30,
            "amount_subtotal"=>55,
            'percentage_tax' => 20,
            'payment_intent_id' =>"3j4ijojewri324",
        ]);
        //user_2 second order
        $order_4 = Order::create([
            'created_at' =>(new Carbon("2022-09-05")),
            "status"=>"Paid",
            "amount_total"=>165,
            "amount_tax"=>40,
            "amount_subtotal"=>110,
            "user_id"=>$this->user_2->id,
            "shipping_address_id"=>$shipping_address_2->id,
            "shipping_method_id"=>$shipping_method->id,
            'percentage_tax' => 15,
            'payment_intent_id' =>"3j4ijwerojewri324",
        ]);
        //user_2 third order
        $order_5 = Order::create([
            'created_at' =>(new Carbon("2023-09-05")),
            "status"=>"Paid",
            "amount_total"=>175,
            "amount_tax"=>40,
            "amount_subtotal"=>110,
            "user_id"=>$this->user_2->id,
            "shipping_address_id"=>$shipping_address_2->id,
            "shipping_method_id"=>$shipping_method->id,
            'percentage_tax' => 10,
            'payment_intent_id' =>"3j4ijwjfeerojewri324",
        ]);
        //create order_details for each order (represent products)
        $order_detail_1 = OrderDetail::create([
            'canceled_at' => null,
            "order_id"=>$order_1->id,
            "product_id"=>$this->product_1->id,
            "ordered_quantity" => 2,
            "color_id"=>$color_1->id,
            "size_id" =>$size_1->id,
            'amount_total'=> 220,
            'amount_tax'=> 20, 
            'amount_subtotal'=>200,
            'amount_unit' => 100,
            'amount_discount' => 0,
            'sale_id' => null,
        ]);
        $order_detail_2 = OrderDetail::create([
            'canceled_at' => null,
            "order_id"=>$order_1->id,
            "product_id"=>$this->product_2->id,
            "ordered_quantity" => 1,
            "color_id"=>$color_1->id,
            "size_id" =>$size_1->id,
            'amount_total'=> 120,
            'amount_tax'=> 10, 
            'amount_subtotal'=>110,
            'amount_unit' => 55,
            'amount_discount' => 0,
            'sale_id' => null,
        ]);
        OrderDetail::create([
            "order_id"=>$order_2->id,
            "product_id"=>$this->product_2->id,
            "ordered_quantity" => 1,
            "color_id"=>$color_1->id,
            "size_id" =>$size_1->id,
            'amount_total'=> 60,
            'amount_tax'=> 5, 
            'amount_subtotal'=>55,
            'amount_unit' => 55,
            'amount_discount' => 0,
            'sale_id' => null,
            'canceled_at' => null,
        ]);
        OrderDetail::create([
            "order_id"=>$order_3->id,
            "product_id"=>$this->product_1->id,
            "ordered_quantity" => 1,
            "color_id"=>$color_1->id,
            "size_id" =>$size_1->id,
            'amount_total'=> 110,
            'amount_tax'=> 10, 
            'amount_subtotal'=>100,
            'amount_unit' => 100,
            'amount_discount' => 0,
            'sale_id' => null,
            'canceled_at' => null,
        ]);
        OrderDetail::create([
            "order_id"=>$order_4->id,
            "product_id"=>$this->product_2->id,
            "ordered_quantity" => 2,
            "color_id"=>$color_1->id,
            "size_id" =>$size_1->id,
            'amount_total'=> 120,
            'amount_tax'=> 10, 
            'amount_subtotal'=>110,
            'amount_unit' => 55,
            'amount_discount' => 0,
            'sale_id' => null,
            'canceled_at' => null,
        ]);
        OrderDetail::create([
            "order_id"=>$order_5->id,
            "product_id"=>$product_3->id,
            "ordered_quantity" => 1,
            "color_id"=>$color_1->id,
            "size_id" =>$size_1->id,
            'amount_total'=> 55,
            'amount_tax'=> 0, 
            'amount_subtotal'=>55,
            'amount_unit' => 55,
            'amount_discount' => 0,
            'sale_id' => null,
            'canceled_at' => null,        
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
        $request = $this->getJson("/api/users/user/orders",$headers);
        $request->assertOk();
        $data = [
            'orders' => [
                ['id'=>$this->order_1->id]
            ]
        ];
        $metadata = [
            "count"=>1,
            "total_count"=>1,
            "pages_count" => 1, 
            "current_page" => 1,
            "limit" => 50,
        ];
        $response_body = HelperTest::getSuccessResponse($data,$metadata);
        $request->assertJson($response_body);
    }

    public function test_list_orders_unauthenticated_user()
    {
        $request = $this->getJson("/api/users/user/orders");
        $request->assertUnauthorized();
    }

    //handle limit 
    public function test_list_orders_limited()
    {
        $headers = ["Authorization" => "Bearer " . $this->token_2];
        $request = $this->getJson("/api/users/user/orders?page=1&limit=1",$headers);
        $request->assertOk();
        $request->assertJsonFragment([
            'metadata'=>[
                "count"=>1,
                "total_count"=>3,
                "pages_count" => 3, 
                "current_page" => 1,
                "limit" => 1,
            ]
        ]);
    }

    // handle sort by 
    public function test_list_orders_sort_by_total_cost_DESC()
    {        
        $headers = ["Authorization" => "Bearer " . $this->token_2];
        $request = $this->getJson("/api/users/user/orders?sort-by=amount_total-DESC",$headers);
        $request->assertOk();
        $data = [
            'orders'=>[
                ["id"=>$this->order_5->id],
                ["id"=>$this->order_4->id],
                ["id"=>$this->order_3->id]
            ]
        ];
        $metadata = [
            "count"=>3,
            "total_count"=>3,
            "pages_count" => 1, 
            "current_page" => 1,
            "limit" => 50,
        ];
        $response_body = HelperTest::getSuccessResponse($data, $metadata);
        $request->assertJson($response_body);
    }

    public function test_list_orders_sort_by_total_cost_ASC()
    {
        $headers = ["Authorization" => "Bearer " . $this->token_2];
        $request = $this->getJson("/api/users/user/orders?sort+by=amount_total+ASC",$headers);
        $request->assertOk();
        $data = [
            'orders'=>[
                ["id"=>$this->order_3->id],
                ["id"=>$this->order_4->id],
                ['id' =>$this->order_5->id]
            ]
        ];
        $metadata = [
            "count"=>3,
            "total_count"=>3,
            "pages_count" => 1, 
            "current_page" => 1,
            "limit" => 50,
        ];
        $response_body = HelperTest::getSuccessResponse($data, $metadata);
        $request->assertJson($response_body);
    }
    
    public function test_list_orders_sort_by_created_at_DESC()
    {
        $headers = ["Authorization" => "Bearer " . $this->token_2];
        $request = $this->getJson("/api/users/user/orders?sort+by=created_at+DESC",$headers);
        $request->assertOk();
        $data= [
            'orders'=>[
                ["id"=>$this->order_5->id],
                ["id"=>$this->order_4->id],
                ["id"=>$this->order_3->id]
            ]
        ];
        $metadata = [
            "count"=>3,
            "total_count"=>3,
            "pages_count" => 1, 
            "current_page" => 1,
            "limit" => 50,
        ];
        $response_body = HelperTest::getSuccessResponse($data, $metadata);
        $request->assertJson($response_body);
    }
    
    public function test_list_orders_sort_by_created_at_ASC()
    {
        $headers = ["Authorization" => "Bearer " . $this->token_2];
        $request = $this->getJson("/api/users/user/orders?sort+by=created_at+ASC",$headers);
        $request->assertOk();
        $data = [
            'orders'=>[
                ["id"=>$this->order_3->id],
                ["id"=>$this->order_4->id],
                ['id'=>$this->order_5->id],
            ]
        ];
        $metadata = [
            "count"=>3,
            "total_count"=>3,
            "pages_count" => 1, 
            "current_page" => 1,
            "limit" => 50,
        ];
        $response_body  = HelperTest::getSuccessResponse($data,$metadata);
        $request->assertJson($response_body);

    }

    // handle search 
    public function test_list_orders_search()
    {
        $headers = ["Authorization" => "Bearer " . $this->token_2];
        $request = $this->getJson("/api/users/user/orders?q=air+force",$headers);
        $request->assertOk();

        //product of Order_3 is "air force",product of order_5 is "air force 2)
        $data = [
            'orders'=>[
                ['id'=>$this->order_5->id],
                ["id"=>$this->order_3->id]
            ]
        ];
        $metadata = [
            "count"=>2,
            "total_count"=>2,
            "pages_count" => 1, 
            "current_page" => 1,
            "limit" => 50,
        ];
        $response_body  = HelperTest::getSuccessResponse($data,$metadata);
        $request->assertJson($response_body);
    }
}
