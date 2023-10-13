<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\FavoritesList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

use Tests\TestCase;
use Illuminate\Support\Carbon;
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

    public $favorites_list_1;
    public $fav_1;
    public $fav_2;
    public $fav_3;

    public function setUp():void
    {
        parent::setUp();

        // create_users 
        // ['users' => [user_1 ,] , 'tokens' => [token_1 ,] ]
        $users = HelperTest::create_users();
        $this->user_1 = $users['users'][0];
        $this->user_2 = $users['users'][1];
        $this->token_1 = $users['tokens'][0];
        $this->token_2 = $users['tokens'][1];

        // create_products
        // [product_1 , product_2]
        $products = HelperTest::create_products();
        $this->product_1 = $products[0];
        $this->product_2 = $products[1];
        $this->product_3 = $products[2];

        //create_favorites
        $this->favorites_list_1 = FavoritesList::create([
            "name"=>$this->user_2->name . "'s favorites",
            "user_id"=>$this->user_2->id,
            'views_count'=>0,
            'likes_count'=>0,
            'public'=>true
        ]);
        $this->fav_1=Favorite::create(['created_at'=>(new Carbon("2022-09-05")),'product_id'=>$this->product_1->id,'favorites_list_id'=>$this->favorites_list_1->id]);
        $this->fav_2=Favorite::create(['created_at'=>(new Carbon("2021-09-05")),'product_id'=>$this->product_2->id,'favorites_list_id'=>$this->favorites_list_1->id]);
        $this->fav_3=Favorite::create(['created_at'=>(new Carbon("2023-09-05")),'product_id'=>$this->product_3->id,'favorites_list_id'=>$this->favorites_list_1->id]);
    }

    //createFavorite method tests

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
        Favorite::create([
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

    //listByFavoritesList method tests

    public function test_list_by_favorites_list():void
    {
        $request = $this->getJson("api/favorites_lists/".$this->favorites_list_1->id."/favorites");
        $request->assertOk();
        $request->assertJson([
            "favorites" => [
                ["id"=>$this->fav_1->id],
                ["id"=>$this->fav_2->id],
                ["id"=>$this->fav_3->id],
            ],
            "count" => 3,
            "total_count"=>3
        ]);
    }

    public function test_list_by_favorites_list_does_not_exist():void
    {
        $request = $this->getJson("api/favorites_lists/32424/favorites");
        $request->assertBadRequest();
        $request->assertJson(["message"=>"Favorites list does not exist."]);
    }

    public function test_list_by_favorites_list_limited():void
    {
        $request = $this->getJson("api/favorites_lists/".$this->favorites_list_1->id."/favorites?page=1&limit=2");
        $request->assertOk();
        $request->assertJson([
            "favorites" => [
                ["id"=>$this->fav_1->id],
                ["id"=>$this->fav_2->id],
            ],
            "count" => 2,
            "total_count"=>3
        ]);
    }

    public function test_list_by_favorites_list_search():void
    {
        $request = $this->getJson("api/favorites_lists/".$this->favorites_list_1->id."/favorites?q=air+f");
        $request->assertOk();
        $request->assertJson([
            "favorites" => [
                ["id"=>$this->fav_1->id],
                ["id"=>$this->fav_3->id],
            ],
            "count" => 2,
            "total_count"=>2
        ]);
        $request->assertJsonMissing([
            "favorites"=>[
                ["id"=>$this->fav_2->id]
            ]
        ]);
    }

    //listByUser method tests 

    public function test_list_by_user():void
    {
        $headers = ["Authorization" => "Bearer ".$this->token_2];
        $request = $this->getJson("api/users/user/favorites",$headers);

        $request->assertOk();
        $request->assertJson([
            "favorites" => [
                ["id"=>$this->fav_1->id],
                ["id"=>$this->fav_2->id],
                ["id"=>$this->fav_3->id],
            ],
            "count" => 3,
            "total_count"=>3
        ]);
    }

    public function test_list_by_user_unauthenticated():void
    {
        $headers = [];
        $request = $this->getJson("api/users/user/favorites",$headers);
        $request->assertUnauthorized();
        $request->assertJson(["message"=>"Unauthenticated."]);
    }

    public function test_list_by_user_search():void
    {
        $headers = ["Authorization" => "Bearer ".$this->token_2];
        $request = $this->getJson("api/users/user/favorites?q=air+f",$headers);

        $request->assertOk();
        $request->assertJson([
            "favorites" => [
                ["id"=>$this->fav_1->id],
                ["id"=>$this->fav_3->id],
            ],
            "count" => 2,
            "total_count"=>2
        ]);
        $request->assertJsonMissing([
            "favorites"=>[
                ["id"=>$this->fav_2->id]
            ]
        ]);
    }
    
    public function test_list_by_user_sort_by_price_DESC():void
    {
        $headers = ["Authorization" => "Bearer ".$this->token_2];
        $request = $this->getJson("api/users/user/favorites?sort+by=price+DESC",$headers);

        $request->assertOk();
        $request->assertJson([
            "favorites" => [
                ["id"=>$this->fav_1->id],
                ["id"=>$this->fav_3->id],
                ["id"=>$this->fav_2->id],
            ],
            "count" => 3,
            "total_count"=>3
        ]);
    }

    public function test_list_by_user_sort_by_created_at_DESC():void
    {
        $headers = ["Authorization" => "Bearer ".$this->token_2];
        $request = $this->getJson("api/users/user/favorites?sort+by=created_at+DESC",$headers);

        $request->assertOk();
        $request->assertJson([
            "favorites" => [
                ["id"=>$this->fav_3->id],
                ["id"=>$this->fav_1->id],
                ["id"=>$this->fav_2->id],
            ],
            "count" => 3,
            "total_count"=>3
        ]);
    }
}
