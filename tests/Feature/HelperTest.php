<?php

namespace Tests\Feature;
use App\Models\Size;
use App\Models\Color;
use App\Models\Product;
use App\Models\Category;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HelperTest {

    // use RefreshDatabase;
    public static function getSuccessResponse($data,$metadata){
        $response= [ 
            "status" => "success",
            "error" => null,
            "data" => $data,
            "metadata"=> $metadata
        ];
        return $response;
    }

    public static function getFailedResponse($error,$metadata){
        $response = [
            "status" => "failed",
            "error" => $error,
            "data" => null,
            "metadata" => $metadata
        ];
        return $response;
    }

    public static function create_admin(){
        $admin = User::create(["name"=>"admin","email"=>"admin@gmail.com", "password"=>"123321"]);
        $token_admin = $admin->createToken("admin_token",[], Carbon::now()->addDays(1))->plainTextToken;
        $admin->assignRole("admin");

        return ["token" => $token_admin , "user" => $admin];
    }

    public static function create_super_admin(){
        $super_admin = User::create(["name"=>"super admin","email"=>"super.admin@gmail.com", "password"=>"123321"]);
        $token_super_admin = $super_admin->createToken("super_admin_token",[],Carbon::now()->addDays(1))->plainTextToken;
        $super_admin->assignRole("super admin");

        return ["token" => $token_super_admin , "user" => $super_admin];
    }

    public static function create_users(){
        $anonymous_user = User::create(['email'=>"anonymousUser@anonymous.com", "name"=>'anonymous','password'=>"234924ukl"]);
        $anonymous_user->assignRole("anonymous");

        $user_1 = User::create(["email"=>"abc@gmail.com", "password"=>"abdc", "name"=>"abc"]);
        $user_2 = User::create(["email"=>"User2@gmail.com", "password"=>"abdc", "name"=>"fjsabcdio"]);
        $user_3 = User::create(["email"=>"user_3@gmail.com", "password"=>"abdc", "name"=>"asiodfj"]);

        $user_1->assignRole("client");
        $user_2->assignRole("client");
        $user_3->assignRole("client");

        $token_1 = $user_1->createToken("user_token",[],Carbon::now()->addDays(1))->plainTextToken;
        $token_2 = $user_2->createToken("user_token",[],Carbon::now()->addDays(1))->plainTextToken;
        $token_3 = $user_3->createToken("user_token",[],Carbon::now()->addDays(1))->plainTextToken;

        return [
            'users' =>[$user_1,$user_2,$user_3],
            'tokens' =>[$token_1,$token_2,$token_3]
        ];
    }

    public static function create_sizes(){
        $size_1 = Size::create(['size' => 'M 3 / 4 W', 'unit'=>"american"]);
        $size_2 = Size::create(['size' => 'M 2 / 5 W', 'unit'=>"american"]);
        $size_3 = Size::create(['size' => 'M 1 / 3.5 W', 'unit'=>"american"]);

        return [$size_1,$size_2,$size_3];
    }

    public static function create_colors(){
        $color_1 = Color::create(['color' => 'red']);
        $color_2 = Color::create(['color' => 'blue']);
        $color_3 = Color::create(['color' => 'yellow']);

        return [$color_1,$color_2,$color_3];
    }

    public static function create_categories(){
        $category_1 = Category::create(['category' =>"men"]);
        $category_2 = Category::create(["category" =>"women"]);
        $category_3 = Category::create(['category' =>"shoes",'parent' =>$category_1->id]);

        return [$category_1, $category_2, $category_3];
    }
    public static function create_products(){
        $size = Size::create(["size" => "M 9 / 3 W", "unit"=>"american"]);
        $color_1 = Color::create(['color'=>"orange"]);
        $color_2 = Color::create(['color'=>"violet"]);

        $category= Category::create(['category'=>"kids"]);
        
        // create products 
        $product_1 = Product::create([
            // 'id' =>2000,
            'name'=>'air force' ,
            'quantity'=>322 , 
            'category_id' => $category->id,
            'price'=>100,
            'description'=>'air force for men',
            'type'=>'mens shoes',
            'created_at' => Carbon::now()
        ]);
        $product_2 = Product::create([
            // 'id' =>3000,
            'name'=>'Jordan 4' ,
            'quantity'=>322 , 
            'category_id' => $category->id,
            'price'=>55,
            'description'=>'Jordan 4 for men',
            'type'=>'mens shoes',
            'created_at' => Carbon::now()
        ]);
        $product_3 = Product::create([
            // 'id' =>4000,
            'name'=>'air force 2' ,
            'quantity'=>200, 
            'category_id' => $category->id,
            'price'=>60,
            'description'=>'air force 2 for men',
            'type'=>'mens shoes',
            'created_at' => Carbon::now()
        ]);

        $product_1->colors()->attach([$color_2->id]);
        $product_2->colors()->attach([$color_1->id]);
        $product_3->colors()->attach([$color_1->id]);

        $product_1->sizes()->attach([$size->id]);
        $product_2->sizes()->attach([$size->id]);
        $product_3->sizes()->attach([$size->id]);

        $sale = Sale::create([
            'quantity'=>100,
            'product_id' => $product_1->id, 
            "sale_percentage"=>20.00 , 
            "starts_at"=>"2022-1-2" , 
            "ends_at"=>"2024-2-2"
        ]);

        return [$product_1, $product_2, $product_3];
    }

}