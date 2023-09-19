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
//setUpBeforeClass : once for all test cases before setUp
//setUp : once before every test case
class ProductTest extends TestCase
{
    use RefreshDatabase;
    protected static $main_product;
    
    public function setUp():void {
        parent::setUp();
        $category = Category::create(['category' => "men" , 'parent_id' => null]);
        $product = Product::create(['name'=>'air force' ,
        'quantity'=>322 , 
        'category_id' => $category->id,
        'price'=>345,
        'description'=>'air force for men',
        'type'=>'mens shoes',
        'created_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);
        $color = Color::create(['color'=>"red"]);
        $size = Size::create(['size'=>"xl"]);
        $sale = Sale::create(['quantity'=>100,'product_id' => $product->id, "sale_percentage"=>20.00 , "starts_at"=>"2022-1-2" , "ends_at"=>"2024-2-2"]);
        $product->colors()->attach([$color->id]);
        $product->sizes()->attach([$size->id]);
        self::$main_product = $product;
    }
    public function test_retrieve_product(): void
    {   
        $response = $this->getJson('/api/products/' . self::$main_product->id);
        $response->assertStatus(200);
        $response->assertJsonStructure(
            ["product" =>
                [
                    'pk',
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
        );

    }
    public function test_retrieve_product_does_not_exist()
    {
        $request  = $this->getJson("/api/products/" . (self::$main_product->id + 349));
        $request->assertStatus(404);
        $request->assertJson([
            "error" => "Product Not Found."
        ]);
    }

    public function test_create_product_wrong_ability(){
        $user = User::create(['name'=>"user" , 'email'=>'user@gmail.com', 'password'=>'user']);
        $token = $user->createToken("token", ['client'])->plainTextToken;
        $request = $this->withHeaders([
            'accept'=>"application/json",
            'Authorization' =>"Bearer ".$token
        ])->postJson("/api/products");
        $request->assertForbidden();
    }

    public function test_create_product_admin_ability(){
        $user = User::create(['name'=>"admin" , 'email'=>'admin@gmail.com', 'password'=>'admin']);
        $token = $user->createToken("token", ['admin'])->plainTextToken;
        $body = [
            "name" => "leather jacket",
            "colors" => ['black','red'], 
            'sizes' => ['xl', 'l','m','s'],
            'category' => ['men', 'shoes', 'sport'],
            'price' => 240,
            'released_at' => '2024-10-10 00:00:00',
            'quantity' =>300,
            'description'=> 'jacket made of leather',
            'images' => []
        ];

        $request = $this->withHeaders([
            'accept'=>"application/json",
            'Authorization' =>"Bearer ".$token
        ])->postJson("/api/products",$body);
        $request->assertCreated();
    }
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