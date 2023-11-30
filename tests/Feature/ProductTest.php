<?php

namespace Tests\Feature;
use App\Models\Size;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use DateTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use PHPUnit\TextUI\Help;

//setUpBeforeClass : once for all test cases before setUp
//setUp : once before every test case
class ProductTest extends TestCase
{
    use RefreshDatabase;

    private $product_1;
    private $product_2;
    private $product_3;

    public function setUp():void {
        parent::setUp();
        $products = HelperTest::create_products();
        $this->product_1 = $products[0];
        $this->product_2 = $products[1];
        $this->product_3 = $products[2];
    }

    // test list products 
    public function test_list_products():void 
    {
        $request = $this->getJson("/api/products");
        $request->assertOk();
        $request->assertJsonFragment([
            "metadata"=>[
                "count" => 3,
                "total_count" => 3,
                "pages_count" => 1, 
                "current_page" =>1,
                "limit" => 50,
            ]
        ]);
    }

    public function test_list_products_search():void
    {
        $request = $this->getJson("/api/products?q=air");
        $request->assertOk();
        $request->assertJsonFragment([
            "metadata"=>[
                "count" => 2,
                "total_count" => 2,
                "pages_count" => 1, 
                "current_page" =>1,
                "limit" => 50,
            ]
        ]); 
    }

    public function test_list_products_search_sort_price_desc():void
    {
        $request = $this->getJson("/api/products?q=air&sort+by=Price+DESC");
        $request->assertStatus(200);
        $data = [
            "products"=>[
                ["id"=>$this->product_1->id],
                ["id"=>$this->product_3->id]
            ],
        ];
        $metadata = [
            "count" => 2,
            "total_count" => 2,
            "pages_count" => 1, 
            "current_page" =>1,
            "limit" => 50,
        ];
        $response_body = HelperTest::getSuccessResponse($data,$metadata);
        $request->assertJson($response_body);
    }
    
    public function test_list_products_filter_by_color():void
    {
        $request = $this->getJson("/api/products?color=violet");
        $request->assertStatus(200);
        $data = [
            'products' =>[
                ["id"=>$this->product_1->id],
            ]
        ];
        $metadata = [
            "count" => 1,
            "total_count" => 1,
            "pages_count" => 1, 
            "current_page" =>1,
            "limit" => 50,
        ];
        $response_body = HelperTest::getSuccessResponse($data,$metadata);
        $request->assertJson($response_body);
    }

    public function test_list_products_search_limited():void
    {
        $request = $this->getJson("/api/products?q=air+f&limit=1");
        $request->assertOk();
        $request->assertJsonFragment([
            'metadata'=>[
                "count"=>1,
                'total_count'=>2,
                'pages_count'=>2,
                "current_page"=>1,
                "limit"=>1,
            ]
        ]);
    }


    // retrive methods
    public function test_retrieve_product(): void
    {   
        $request = $this->getJson('/api/products/' . $this->product_1->id);
        $request->assertStatus(200);
        $request->assertJsonStructure([
            'metadata',
            'status',
            'error',
            "data" =>[
                'product'=>[
                    'id',
                    'name',
                    'quantity',
                    'price',
                    'description',
                    'type',
                    'added_at',
                    'colors' => [] ,
                    'sizes' => [] ,
                    'sale' => [
                        'price_after_sale',
                        'percentage',
                        'starts_at',
                        'ends_at' 
                    ], 
                    'category',
                    'images' => [
                    ]
                ]
            ]
        ]);
    }

    public function test_retrieve_product_does_not_exist()
    {
        $request  = $this->getJson("/api/products/" . ($this->product_1->id + 349));
        $request->assertNotFound();
    }

    // public function test_create_product_wrong_ability(){
    //     $user = User::create(['name'=>"user" , 'email'=>'user@gmail.com', 'password'=>'user']);
    //     $token = $user->createToken("token",[])->plainTextToken;
    //     $headers = ['accept'=>"application/json",'Authorization' =>"Bearer ".$token];

    //     $request = $this->postJson("/api/products",[],$headers);
    //     $request->assertForbidden();
    // }

    // public function test_create_product_admin_ability(){
    //     $adminTokenArr = HelperTest::create_admin();
    //     $admin = $adminTokenArr['user'];
    //     $admin_token = $adminTokenArr['token'];

    //     $headers = ['accept'=>"application/json",'Authorization' =>"Bearer ".$admin_token];
    //     $body = [
    //         "name" => "leather jacket",
    //         "colors" => ['black','red'], 
    //         'sizes' => ['xl', 'l','m','s'],
    //         'category' => ['men', 'shoes', 'sport'],
    //         'price' => 240,
    //         'released_at' => '2024-10-10 00:00:00',
    //         'quantity' =>300,
    //         'description'=> 'jacket made of leather',
    //         'images' => []
    //     ];

    //     $request = $this->postJson("/api/products",$body,$headers);
    //     $request->assertCreated();
    // }
}


// [
//     'id' : '3',
//     'name' : 'nike air force' ,
//     'quantity' : 322, 
//     'price' : 234 ,
//     'description' : 'air force shoe',
//     'type' : 'mens shoe' ,
//     'created_at' : '2003-04-19T15:00:00.000Z',
//     'colors' : ['red', 'blue', 'yellow'],
//     'sizes' : ['39','41', '23'],
//     'sale': {
//         'price_after_sale' : 123,
//         'percentage' : 50.00 ,
//         'starts_at' : '2023-04-19T15:00:00.000Z',
//         'ends_at' : '2024-04-19T15:00:00.000Z',
//     },
//     'category': "pitch" 
//     'images' :[
//         {

//         },
//         {

//         }
//     ]
// ]