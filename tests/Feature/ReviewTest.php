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


    protected static $review_1;
    protected static $review_2;
    protected static $token_1;
    protected static $token_2;
    protected static $product_1;

    public  function setUp():void{
        parent::setUp();

        // create users
        $users =OrderTest::create_users();
        $user_1 = $users['users'][0];
        $user_2 = $users['users'][1];
        $this->token_1 = $users['tokens'][0];
        $this->token_2 = $users['tokens'][1];

        // create products
        $category = Category::create(['category' => "men" , 'parent_id' => null]);
        $color = Color::create(['color'=>"red"]);
        $this->product_1 = Product::create([
            'name'=>'air force' ,
            'quantity'=>322 , 
            'category_id' => $category->id,
            'price'=>345,
            'description'=>'air force for men',
            'type'=>'mens shoes',
            'created_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);
        $sale = Sale::create([
            'quantity'=>100,
            'product_id' => $this->product_1->id, 
            "sale_percentage"=>20.00 , 
            "starts_at"=>"2022-1-2" , 
            "ends_at"=>"2024-2-2"
        ]);
        $this->product_1->colors()->attach([$color->id]);

        // create reviews for a product 
        self::$review_1 = Review::create([
            'created_at' => (new DateTime('2022-09-02'))->format('Y-m-d H:i:s'),
            'user_height'=> 190,
            'user_weight' => 80,
            'user_id' =>$user_1->id,
            'product_id' =>$this->product_1->id,
            'title'=>"review 1",
            'text' =>'review 1 text',
            // 'size_id' => $size->id,
            'color_id' =>$color->id,
            'helpful_count' =>2,
            'product_rating'=>3.5,
        ]);
        self::$review_2 = Review::create([
            'created_at' => (new DateTime('2024-09-02'))->format('Y-m-d H:i:s'),
            'user_height'=> 150,
            'user_weight' => 60,
            'user_id' =>$user_2->id,
            'product_id' =>$this->product_1->id,
            'title'=>"review 2",
            'text' =>'review 2 text',
            // 'size_id' => $size->id,
            'color_id' =>$color->id,
            'helpful_count' =>14,
            'product_rating'=>2,
        ]);

        // user liked review_2 
        $user_1->liked_reviews()->attach(self::$review_2);
    }

    
    public function test_reviews_by_product(): void
    {
        $request = $this->getJson("/api/products/" . self::$product_1->id ."/reviews");
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
        $request = $this->getJson("/api/products/" . self::$product_1->id ."/reviews?page=1&limit=1");
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
        $request2 = $this->getJson("/api/products/" . self::$product_1->id ."/reviews?limit=1");
        $request2->assertJson(['total_count'=>2]);
        $request2->assertJson(['count' => 1]);

        $request3 = $this->getJson("/api/products/" . self::$product_1->id ."/reviews?limit=10");
        $request3->assertJson(['total_count'=>2]);
        $request3->assertJson(['count' => 2]);

    }

    public function test_reviews_by_product_sort_by_created_at():void
    {
        $request = $this->getJson("/api/products/" . self::$product_1->id ."/reviews?sort-by=created_at_DESC");
        $request->assertJson(['count'=>2]);
        //assert that review_1 is before review_2 
        $request->assertJson([
            'reviews'=>[
                [
                    'id'=>self::$review_1->id,
                ],
                [
                    'id'=>self::$review_2->id,
                ],
                
            ]
        ]);
    }

    public function test_reviews_by_product_sort_by_helpful_count():void
    {
        $request = $this->getJson("/api/products/" . self::$product_1->id ."/reviews?sort-by=helpful_count-DESC");
        $request->assertJson(['count'=>2]);
        //assert that review_2 is before review_1
        $request->assertJson([
            'reviews'=>[
                [
                    'id'=>self::$review_2->id,
                ],
                [
                    'id'=>self::$review_1->id,
                ],
                
            ]
        ]);
    }

    public function test_reviews_by_product_sort_by_helpful_count_asc():void
    {
        $request = $this->getJson("/api/products/" . self::$product_1->id ."/reviews?sort-by=helpful_count-ASC");
        $request->assertJson(['count'=>2]);
        //assert that review_2 is before review_1
        $request->assertJson([
            'reviews'=>[
                [
                    'id'=>self::$review_1->id,
                ],
                [
                    'id'=>self::$review_2->id,
                ],
                
            ]
        ]);
    }


    public function test_liked_reviews_by_product():void
    {
        $headers= ['Authorization' => "Bearer " .self::$token_1];
        $request = $this->getJson("/api/products/".self::$product_1->id."/user/reviews/liked",$headers);
        $request->assertOk();
        $request->assertJson([self::$review_2->id]);
    }
    public function test_liked_reviews_by_product_Unauthenticated():void
    {
        $headers= [];
        $request = $this->getJson("/api/products/".self::$product_1->id."/user/reviews/liked",$headers);
        $request->assertUnauthorized();
    }

    public function test_like_review():void
    {   
        $headers= ['Authorization' => "Bearer " .self::$token_1];
        $previous_helpful_count = self::$review_1->helpful_count;
        $request = $this->postJson("/api/reviews/".self::$review_1->id."/like",[],$headers);
        $request->assertOk();
        self::$review_1->refresh();
        $this->assertEquals(self::$review_1->helpful_count,$previous_helpful_count+1);
        $request->assertJson(["helpful_count"=>self::$review_1->helpful_count,"action"=>"added"]);
    }
    
    public function test_like_review_again():void
    {
        $previous_helpful_count = self::$review_2->helpful_count;
        $headers= ['Authorization' => "Bearer " .self::$token_1];

        // review_2 already liked by user of token_1 
        $request = $this->postJson("/api/reviews/".self::$review_2->id."/like",[],$headers);
        $request->assertOk();
        self::$review_2->refresh();

        // like removed
        $this->assertEquals(self::$review_2->helpful_count,$previous_helpful_count-1);
        $request->assertJson(["helpful_count"=>self::$review_2->helpful_count,"action"=>"removed"]);
    }
    public function test_like_review_unauthenticated():void
    {
        $previous_helpful_count = self::$review_1->helpful_count;
        $headers= [];
        $request = $this->postJson("/api/reviews/" . self::$review_1->id. "/like",[],$headers);
        $request->assertUnauthorized();

        //helpful_count did not change
        $this->assertEquals(self::$review_1->helpful_count,$previous_helpful_count);
    }
    public function test_like_review_does_not_exist():void
    {
        $headers= ['Authorization' => "Bearer " .self::$token_2];

        // review of id 298 does not exist
        $request = $this->postJson("/api/reviews/298/like",[],$headers);
        $request->assertNotFound();
        $request->assertJson(["error"=>"review not found."]);
    }


    // createReview tests 

    // public function test_create_review():void
    // {
    //     $data = [
    //         "product_id" => self::$product_1->id,
    //         "text" => "nice material and the size is perfect",
    //         "title" => "amazing",
    //         "user_height"=>190,
    //         "user_weight" =>80,
    //         'size' => 'l',
    //         'color' =>'red',
    //         'images' => [
    //             'url1',
    //             'url2',
    //             'url3'
    //         ]
    //     ];
    //     $request = $this->postJson("/api/reivews", $data , [
    //         "content-type"=>"application/json",
    //         'accept'=>'application/json',
    //         'Authorization' => "Bearer " .self::$token_1
    //     ]);
    // }

}
