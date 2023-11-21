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
use Carbon\Carbon;
use Database\Seeders\UserRolePermissionSeeder;
use Tests\Feature\HelperTest;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private $user_1;
    private $review_1;
    private $review_2;
    private $token_1;
    private $token_2;
    private $product_1;
    private $product_2;

    public  function setUp():void{
        parent::setUp();
        $this->seed(UserRolePermissionSeeder::class);

        // create users
        $users =HelperTest::create_users();
        $user_1 = $users['users'][0];
        $user_2 = $users['users'][1];
        $this->user_1 = $user_1;
        $this->token_1 = $users['tokens'][0];
        $this->token_2 = $users['tokens'][1];

        // create products
        $products = HelperTest::create_products();
        $this->product_1 = $products[0];
        $this->product_2 = $products[1];
        $color = HelperTest::create_colors()[0];
     
        // create reviews for a product 
        $this->review_1 = Review::create([
            'created_at' => (new DateTime('2022-09-02'))->format('Y-m-d H:i:s'),
            'user_height'=> 190,
            'user_weight' => 80,
            'user_id' =>$user_1->id,
            'product_id' =>$this->product_1->id,
            'title'=>"review 1",
            'text' =>'review 1 text',
            'color_id' =>$color->id,
            'helpful_count' =>1,
            'product_rating'=>3.5,
        ]);
        $this->review_2 = Review::create([
            'created_at' => (new DateTime('2024-09-02'))->format('Y-m-d H:i:s'),
            'user_height'=> 150,
            'user_weight' => 60,
            'user_id' =>$user_2->id,
            'product_id' =>$this->product_1->id,
            'title'=>"review 2",
            'text' =>'review 2 text',
            'color_id' =>$color->id,
            'helpful_count' =>1,
            'product_rating'=>2,
        ]);

        // user liked review_2 
        $user_1->liked_reviews()->attach($this->review_2);
        $user_1->liked_reviews()->attach($this->review_1);
        $user_2->liked_reviews()->attach($this->review_1);
    }

    // tests of list reviews 
    public function test_list_reviews_by_product(): void
    {
        $request = $this->getJson("/api/products/" . $this->product_1->id ."/reviews");
        $request->assertStatus(200);
        $request->assertJsonStructure([
            'data',
            'metadata',
            'error',
            'status'
        ]);
        $request->assertJsonFragment([
            'metadata'=>[
                'average_rating'=>2.75,
                "count"=>2,
                "total_count"=>2,
                "pages_count" => 1, 
                "current_page" => 1,
                "limit" => 50,
            ]
        ]);
    }
    public function test_list_reviews_by_product_authenticated():void
    {
        $headers= ['Authorization' => "Bearer " .$this->token_1];
        $request = $this->getJson("/api/products/".$this->product_1->id."/reviews",$headers);
        $request->assertOk();
        
        $data = [ 
            'reviews' =>[
                ['id'=>$this->review_1->id, 'is_liked_by_current_user' => true],
                ['id'=>$this->review_2->id, 'is_liked_by_current_user' => true],
            ]
        ];
        $metadata = [
            "count"=>2,
            "total_count"=>2,
            "pages_count" => 1, 
            "current_page" => 1,
            "limit" => 50,
        ];
        $request->assertJson(HelperTest::getSuccessResponse($data,$metadata));
    }

    public function test_list_reviews_by_product_Unauthenticated():void
    {
        $headers= [];
        $request = $this->getJson("/api/products/".$this->product_1->id."/reviews",$headers);

        $data = [ 
            'reviews' =>[
                ['id'=>$this->review_1->id, 'is_liked_by_current_user' => false],
                ['id'=>$this->review_2->id, 'is_liked_by_current_user' => false],
            ]
        ];
        $metadata = [
            "count"=>2,
            "total_count"=>2,
            "pages_count" => 1, 
            "current_page" => 1,
            "limit" => 50,      
        ];
        $request->assertJson(HelperTest::getSuccessResponse($data,$metadata));
    }

    public function test_list_reviews_by_product_limit(): void
    {
        $request = $this->getJson("/api/products/" . $this->product_1->id ."/reviews?page=1&limit=1");
        $request->assertOk();
        $request->assertJsonFragment([
            'metadata'=>[
                "count"=>1,
                "total_count"=>2,
                "pages_count" => 2, 
                "current_page" => 1,
                "limit" => 1,
                'average_rating' => 2.75,
            ]
        ]);

        // if no page provided , no products are skipped , just limited
        $request2 = $this->getJson("/api/products/" . $this->product_1->id ."/reviews?limit=1");
        $request2->assertOk();
        $request2->assertJsonFragment([
            'metadata'=>[
                "count"=>1,
                "total_count"=>2,
                "pages_count" => 2, // ceil of total_count/limit
                "current_page" => 1,
                "limit" => 1,
                'average_rating' => 2.75,
            ]
        ]);

        $request3 = $this->getJson("/api/products/" . $this->product_1->id ."/reviews?limit=10");
        $request3->assertOk();
        $request3->assertJsonFragment([
            'metadata'=>[
                "count"=>2,
                "total_count"=>2,
                "pages_count" => 1, 
                "current_page" => 1,
                "limit" => 10,
                'average_rating' => 2.75,
            ]
        ]);
    }

    public function test_list_reviews_by_product_sort_by_created_at():void
    {
        $request = $this->getJson("/api/products/" . $this->product_1->id ."/reviews?sort+by=created_at+DESC");
        $request->assertOk();
        $data = [ 
            'reviews' =>[
                ['id'=>$this->review_1->id],
                ['id'=>$this->review_2->id],
            ]
        ];
        $metadata = [
            "count"=>2,
            "total_count"=>2,
            "pages_count" => 1, 
            "current_page" => 1,
            "limit" => 50,
            'average_rating' => 2.75,
        ];
        $request->assertJson(HelperTest::getSuccessResponse($data,$metadata));
    }

    public function test_list_reviews_by_product_sort_by_helpful_count_desc():void
    {
        $request = $this->getJson("/api/products/" . $this->product_1->id ."/reviews?sort+by=helpful_count+DESC");
        $request->assertOk();

        $data = [ 
            'reviews' =>[
                ['id'=>$this->review_1->id],
                ['id'=>$this->review_2->id],
            ]
        ];
        $metadata = [
            "count"=>2,
            "total_count"=>2,
            "pages_count" => 1, 
            "current_page" => 1,
            "limit" => 50,
        ];
        $request->assertJson(HelperTest::getSuccessResponse($data,$metadata));
    }

    public function test_list_reviews_by_product_sort_by_helpful_count_asc():void
    {
        $request = $this->getJson("/api/products/" . $this->product_1->id ."/reviews?sort+by=helpful_count+ASC");
        //assert that review_2 is before review_1
        $data = [ 
            'reviews' =>[
                ['id'=>$this->review_1->id],
                ['id'=>$this->review_2->id],
            ]
        ];
        $metadata = [
            "count"=>2,
            "total_count"=>2,
            "pages_count" => 1, 
            "current_page" => 1,
            "limit" => 50,
        ];
        $request->assertJson(HelperTest::getSuccessResponse($data,$metadata));
    }

    // tests of like review
    public function test_like_review():void
    {   
        $headers= ['Authorization' => "Bearer " .$this->token_2];
        $previous_helpful_count = $this->review_2->helpful_count;
        $request = $this->postJson("/api/reviews/".$this->review_2->id."/like",[],$headers);
        $request->assertOk();
        $this->review_2->refresh();
        $this->assertEquals($this->review_2->helpful_count,$previous_helpful_count+1);
        $data = ['action'=>'liked'];
        $metadata = ['helpful_count'=>$this->review_2->helpful_count];
        $request->assertJson(HelperTest::getSuccessResponse($data,$metadata));
    }
    
    public function test_like_review_again():void
    {
        $previous_helpful_count = $this->review_2->helpful_count;
        $headers= ['Authorization' => "Bearer " .$this->token_1];

        // review_2 already liked by user of token_1 
        $request = $this->postJson("/api/reviews/".$this->review_2->id."/like",[],$headers);
        $request->assertOk();
        $this->review_2->refresh();

        // like removed        
        $data = ['action'=>'unliked'];
        $metadata = ['helpful_count'=>$this->review_2->helpful_count];
        $request->assertJson(HelperTest::getSuccessResponse($data,$metadata));
        $this->assertEquals($this->review_2->helpful_count,$previous_helpful_count-1);

    }

    public function test_like_review_unauthenticated():void
    {
        $previous_helpful_count = $this->review_1->helpful_count;
        $headers= [];
        $request = $this->postJson("/api/reviews/" . $this->review_1->id. "/like",[],$headers);
        $request->assertUnauthorized();

        //helpful_count did not change
        $this->assertEquals($this->review_1->helpful_count,$previous_helpful_count);
    }

    public function test_like_review_does_not_exist():void
    {
        $headers= ['Authorization' => "Bearer " .$this->token_2];
        // review of id 298 does not exist
        $request = $this->postJson("/api/reviews/298/like",[],$headers);
        $request->assertNotFound();
        $error = ['message'=>"Review not found.",'code'=>404];
        $request->assertJson(HelperTest::getFailedResponse($error,null));
    }


    // test delete reviews 
    public function test_delete_review():void
    {
        $review = Review::create([
            'created_at' => (new DateTime('2022-09-02'))->format('Y-m-d H:i:s'),
            'user_height'=> 190,
            'user_weight' => 80,
            'user_id' =>$this->user_1->id,
            'product_id' =>$this->product_2->id,
            'title'=>"review 3",
            'text' =>'review 3 text',
            'helpful_count' =>1,
            'product_rating'=>3.5,
        ]);

        $headers= ['Authorization' => "Bearer " .$this->token_1];
        $request = $this->deleteJson("/api/reviews/".$review->id , [] , $headers);
        $request->assertOk();        
        
        $data=  ['action' => 'deleted'];
        $metadata = [
            "total_count"=>0,
            'average_ratings'=>0
        ];
        $request->assertJson(HelperTest::getSuccessResponse($data,$metadata));

        $review_deleted = Review::find($review->id);
        $this->assertNull($review_deleted);
    }

    public function test_delete_review_by_admin():void
    {
        
        $adminTokenArr = HelperTest::create_admin();
        $admin = $adminTokenArr['user'];
        $admin_token = $adminTokenArr['token'];

        $review = Review::create([
            'created_at' => (new DateTime('2022-09-02'))->format('Y-m-d H:i:s'),
            'user_height'=> 190,
            'user_weight' => 80,
            'user_id' =>$this->user_1->id,
            'product_id' =>$this->product_2->id,
            'title'=>"review 3",
            'text' =>'review 3 text',
            'helpful_count' =>1,
            'product_rating'=>3.5,
        ]);
        
        $headers= ['Authorization' => "Bearer " .$admin_token];
        $request = $this->deleteJson("/api/reviews/".$review->id , [] , $headers);
        $request->assertOk();

        $review_deleted = Review::find($review->id);
        $this->assertNull($review_deleted);
    }

    // public function test_delete_review_by_other_user():void
    // {
    //     $review = Review::create([
    //         'created_at' => (new DateTime('2022-09-02'))->format('Y-m-d H:i:s'),
    //         'user_height'=> 190,
    //         'user_weight' => 80,
    //         'user_id' =>$this->user_1->id,
    //         'product_id' =>$this->product_2->id,
    //         'title'=>"review 3",
    //         'text' =>'review 3 text',
    //         'helpful_count' =>1,
    //         'product_rating'=>3.5,
    //     ]);

    //     $headers= ['Authorization' => "Bearer " .$this->token_2]; // user 2 deleting review of user 1
    //     $request = $this->deleteJson("/api/reivews/"+$review->id , [] , $headers);
    //     $request->assertForbidden();
    //     $request->assertJson([
    //         'action'=>'not deleted.',
    //         'message' => 'You do not have permission to delete this review.'
    //     ]);

    //     $review_deleted = Review::find($review->id);
    //     $this->assertNotNull($review_deleted);
    // }
    // public function test_delete_review_does_not_exist():void
    // {
    //     $headers= ['Authorization' => "Bearer " .$this->token_1];
    //     $request = $this->deleteJson("/api/reivews/"+12312 , [] , $headers);
    //     $request->assertBadRequest();
    //     $request->assertJson(['message'=>'Review does not exist.']);
        
    // }

    // createReview tests 

    // public function test_create_review():void
    // {
    //     $data = [
    //         "product_id" => $this->product_1->id,
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
    //         'Authorization' => "Bearer " .$this->token_1
    //     ]);
    // }

}
