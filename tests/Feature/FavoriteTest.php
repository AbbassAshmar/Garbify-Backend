<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\FavoritesList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

use Tests\TestCase;

class FavoriteTest extends TestCase
{

    use RefreshDatabase;

    public $user_1;
    public $user_2;
    public $token_1;
    public $token_2;
    public $product_1;
    public $product_2;
    public $product_3;

    public function setUp():void
    {
        parent::setUp();

        // create_users 
        // ['users' => [user_1 ,] , 'tokens' => [token_1 ,] ]
        $users =OrderTest::create_users();
        $this->user_1 = $users['users'][0];
        $this->user_2 = $users['users'][1];
        $this->token_1 = $users['tokens'][0];
        $this->token_2 = $users['tokens'][1];

        // create_products
        // [product_1 , product_2]
        $products = OrderTest::create_products();
        $this->product_1 = $products[0];
        $this->product_2 = $products[1];
        $this->product_3 = $products[2];
    }

    public function test_create_first_favorite(): void
    {
        $data = ["product_id" => $this->product_1->id];
        $headers=["Authorization"=>"Bearer " . $this->token_1];
        
        $request= $this->postJson("/api/favorites",$data,$headers);
        $request->assertCreated();
        $request->assertJsonStructure([
            'action',
            'favorite'=>[
                "id",
                'created_at',
                'favorites_list_id',
                'product_id'=>[],
            ]
        ]);

        // new favorites_list created for the user on first favorite creation
        $favorites_list = FavoritesList::where("user_id", $this->user_1->id)->first();
        $this->assertNotNull($favorites_list);
    }

    public function test_create_second_favorite():void
    {
        $list= FavoritesList::create([
            'user_id' => $this->user_1->id,
            'name' =>$this->user_1->name ."'s Favorites",
        ]);
        $fav_1= Favorite::create([
            'favorites_list_id' =>$list->id,
            'product_id' =>$this->product_1->id,
        ]);

        $data = ['product_id' =>$this->product_2->id];
        $headers=["Authorization" => "Bearer ".$this->token_1];
        $request = $this->postJson('/api/favorites', $data , $headers);
        $request->assertCreated();
        $request->assertJsonStructure([
            'action',
            'favorite'=>[
                "id",
                'created_at',
                'favorites_list_id',
                'product_id'=>[],
            ]
        ]);

        $favorites_list = FavoritesList::where("user_id", $this->user_1->id)->first();
        $favorites_count = $favorites_list->favorites()->count();
        $this->assertEquals(2,$favorites_count);
    }

    public function test_create_favorite_unauthenticated(): void
    {
        $data = ["product_id" => $this->product_1->id];
        $headers=[];
        $request= $this->postJson("/api/favorites",$data,$headers);
        $request->assertUnauthorized();
    }
    
    public function test_create_favorite_product_does_not_exist(): void
    {
        $data = ["product_id" => 32443];
        $headers=["Authorization" => "Bearer " . $this->token_1];
        $request= $this->postJson("/api/favorites",$data,$headers);
        $request->assertBadRequest();
        $request->assertJson(['message' =>'product not found.']);
    }
    
    public function test_delete_favorite():void
    {
        $list= FavoritesList::create([
            'user_id' => $this->user_1->id,
            'name' =>$this->user_1->name ."'s Favorites",
        ]);
        $fav_1= Favorite::create([
            'favorites_list_id' =>$list->id,
            'product_id' =>$this->product_1->id,
        ]);

        //post the same  favorite (delete it) 
        $data = ['product_id' => $this->product_1->id];
        $headers=["Authorization" => "Bearer " . $this->token_1];
        $request= $this->postJson("/api/favorites" ,$data,$headers);
        $request->assertOk();
        $request->assertJson([
            'action' => 'deleted',
        ]);
        $all_fav = $list->favorites()->count();
        $this->assertEquals(0, $all_fav);
    }

}
