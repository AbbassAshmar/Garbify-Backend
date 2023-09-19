<?php

namespace Tests\Feature;
use App\Models\Product;
use App\Models\Category;
use App\Models\Size;
use App\Models\Color;
use App\Models\Review;
use DateTime;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
class ReviewTest extends TestCase
{
    use RefreshDatabase;


    protected static $review1;
    protected static $review2;
    protected static $product1;
    protected static $token1;
    protected static $token2;
    public  function setUp():void{
        parent::setUp();


        // create a user  

        $user = User::where("email",'abc2332@gmail.com')->first();
        $user2 = User::where("email", "acc@gmail.com")->first();
        if (!$user){ 
            $user = User::create(['name'=>"a342bc", 'email'=>'abc2332@gmail.com', 'password'=>"abcdefg34"]);
            $token1=$user->createToken("token", ['client'])->plainTextToken;
            self::$token1 = $token1;
        }
        if (!$user2){ 
            $user2 = User::create(['name'=>"abc", 'email'=>"acc@gmail.com", 'password'=>"abcdefg34"]);
            $token2=$user->createToken("token", ['client'])->plainTextToken;
            self::$token2 = $token2;

        }

        // create a product

        $category = Category::create(['category' => "men" , 'parent_id' => null]);
        $product = Product::create(['name'=>'air force' ,
        'quantity'=>322 , 
        'category_id' => $category->id,
        'price'=>345,
        'description'=>'air force for men',
        'type'=>'mens shoes',
        'created_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);
        self::$product1 = $product;
        $color = Color::create(['color'=>"red"]);
        $sale = Sale::create(['quantity'=>100,'product_id' => $product->id, "sale_percentage"=>20.00 , "starts_at"=>"2022-1-2" , "ends_at"=>"2024-2-2"]);
        $product->colors()->attach([$color->id]);
       

        // create reviews for a product 

        self::$review1 = Review::create([
            'created_at' => (new DateTime('2022-09-02'))->format('Y-m-d H:i:s'),
            'user_height'=> 190,
            'user_weight' => 80,
            'user_id' =>$user->id,
            'product_id' =>$product->id,
            'title'=>"review 1",
            'text' =>'review 1 text',
            // 'size_id' => $size->id,
            'color_id' =>$color->id,
            'helpful_count' =>2,
            'product_rating'=>3.5,
        ]);
        self::$review2 = Review::create([
            'created_at' => (new DateTime('2024-09-02'))->format('Y-m-d H:i:s'),
            'user_height'=> 150,
            'user_weight' => 60,
            'user_id' =>$user2->id,
            'product_id' =>$product->id,
            'title'=>"review 2",
            'text' =>'review 2 text',
            // 'size_id' => $size->id,
            'color_id' =>$color->id,
            'helpful_count' =>14,
            'product_rating'=>2,
        ]);

        // user liked review2 
        $user->liked_reviews()->attach(self::$review2);


    }

    
    public function test_reviews_by_product(): void
    {
        $request = $this->getJson("/api/products/" . self::$product1->id ."/reviews");
        $request->assertStatus(200);
        $request->assertJsonStructure(
            [
                'reviews' , 
                'total_count',
                'count'
            ]
        );
        $request->assertJson(['total_count'=>2]);
    }
    public function test_reviews_by_product_limit(): void
    {
        $request = $this->getJson("/api/products/" . self::$product1->id ."/reviews?page=1&limit=1");
        $request->assertStatus(200);
        $request->assertJsonStructure(
            [
                'reviews' , 
                'total_count',
                'count'
            ]
        );
        $request->assertJson(['total_count'=>2]);
        $request->assertJson(['count' => 1]);

        // if no page provided , no products are skipped , just is limited
        $request2 = $this->getJson("/api/products/" . self::$product1->id ."/reviews?limit=1");
        $request2->assertJson(['total_count'=>2]);
        $request2->assertJson(['count' => 1]);

        $request3 = $this->getJson("/api/products/" . self::$product1->id ."/reviews?limit=10");
        $request3->assertJson(['total_count'=>2]);
        $request3->assertJson(['count' => 2]);

    }
    public function test_reviews_by_product_sort_by_created_at():void
    {
        $request = $this->getJson("/api/products/" . self::$product1->id ."/reviews?sort-by=created_at_DESC");
        $request->assertJson(['count'=>2]);
        //assert that review1 is before review2 
        $request->assertJson([
            'reviews'=>[
                [
                    'id'=>self::$review1->id,
                ],
                [
                    'id'=>self::$review2->id,
                ],
                
            ]
        ]);
    }
    public function test_reviews_by_product_sort_by_helpful_count():void
    {
        $request = $this->getJson("/api/products/" . self::$product1->id ."/reviews?sort-by=helpful_count-DESC");
        $request->assertJson(['count'=>2]);
        //assert that review2 is before review1
        $request->assertJson([
            'reviews'=>[
                [
                    'id'=>self::$review2->id,
                ],
                [
                    'id'=>self::$review1->id,
                ],
                
            ]
        ]);
    }
    public function test_reviews_by_product_sort_by_helpful_count_asc():void
    {
        $request = $this->getJson("/api/products/" . self::$product1->id ."/reviews?sort-by=helpful_count-ASC");
        $request->assertJson(['count'=>2]);
        //assert that review2 is before review1
        $request->assertJson([
            'reviews'=>[
                [
                    'id'=>self::$review1->id,
                ],
                [
                    'id'=>self::$review2->id,
                ],
                
            ]
        ]);
    }


    public function test_liked_reviews_by_product():void
    {
        $request = $this->getJson("/api/products/" . self::$product1->id. "/user/reviews/liked",[
            "content-type"=>"application/json",
            'accept'=>'application/json',
            'Authorization' => "Bearer " .self::$token1
        ]);
        echo($request->content());
        $request->assertOk();
        $request->assertJson([self::$review2->id]);
    }
    public function test_liked_reviews_by_product_Unauthenticated():void
    {
        $request = $this->getJson("/api/products/" . self::$product1->id. "/user/reviews/liked",[
            "content-type"=>"application/json",
            'accept'=>'application/json',
        ]);
        $request->assertUnauthorized();
    }



    public function test_like_review():void
    {   
        $previous_helpful_count = self::$review1->helpful_count;
        $request = $this->postJson("/api/reviews/" . self::$review1->id. "/like",[],[
            "content-type"=>"application/json",
            'accept'=>'application/json',
            'Authorization' => "Bearer " .self::$token1

        ]);
        $request->assertOk();
        self::$review1->refresh();
        $this->assertEquals(self::$review1->helpful_count,$previous_helpful_count+1);
        $request->assertJson(["helpful_count"=>self::$review1->helpful_count,"action"=>"like added"]);
    }
    
    public function test_like_review_again():void
    {
        $previous_helpful_count = self::$review2->helpful_count;
        // review2 already liked by user of token1 
        $request = $this->postJson("/api/reviews/" . self::$review2->id. "/like",[],[
            "content-type"=>"application/json",
            'accept'=>'application/json',
            'Authorization' => "Bearer " .self::$token1
        ]);
        $request->assertOk();
        self::$review2->refresh();
        // like removed
        $this->assertEquals(self::$review2->helpful_count,$previous_helpful_count-1);
        $request->assertJson(["helpful_count"=>self::$review2->helpful_count,"action"=>"like removed"]);
    }
    public function test_like_review_unauthenticated():void
    {
        $previous_helpful_count = self::$review1->helpful_count;
        $request = $this->postJson("/api/reviews/" . self::$review1->id. "/like",[],[
            "content-type"=>"application/json",
            'accept'=>'application/json',
        ]);
        $request->assertUnauthorized();
        //helpful_count did not change
        $this->assertEquals(self::$review1->helpful_count,$previous_helpful_count);
    }
    public function test_like_review_does_not_exist():void
    {
        // review of id 298 does not exist
        $request = $this->postJson("/api/reviews/298/like",[],[
            "content-type"=>"application/json",
            'accept'=>'application/json',
            'Authorization' => "Bearer " .self::$token2
        ]);
        $request->assertNotFound();
        $request->assertJson(["error"=>"review not found."]);
    }


    // createReview tests 

    public function test_create_review():void
    {
        $data = [
            "product_id" => self::$product1->id,
            "text" => "nice material and the size is perfect",
            "title" => "amazing",
            "user_height"=>190,
            "user_weight" =>80,
            'size' => 'l',
            'color' =>'red',
            'images' => [
                'url1',
                'url2',
                'url3'
            ]
        ];
        $request = $this->postJson("/api/reivews", $data , [
            "content-type"=>"application/json",
            'accept'=>'application/json',
            'Authorization' => "Bearer " .self::$token1
        ]);
    }

}
