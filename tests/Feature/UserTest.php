<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    protected static $user;
    protected static $token;

    public function setUp():void
    {
        parent::setUp();
        $user1 = User::where("email",'abc2332@gmail.com')->first();
        if (!$user1){ 
            self::$user = User::create(['name'=>"a342bc", 'email'=>'abc2332@gmail.com', 'password'=>"abcdefg34"]);
            self::$token=self::$user->createToken("token", ['client'])->plainTextToken;
        }else{
            self::$user =$user1;
        }
    }
    public function test_admin_register_unauthorized(): void
    {
        $request = $this->postJson("/api/register/admin");
        $request->assertUnauthorized();
    }
    public function test_admin_register_wrong_ability():void
    {
        $request = $this->withHeaders([
            'Authorization' => 'Bearer ' .self::$token,
        ])->postJson("/api/register/admin");
        $request->assertStatus(403);
    }
    public function test_admin_register_accepted():void 
    {
        $user = User::create(['name' => "aaa" , 'email' =>"aaa@gmail.com" , "password" =>"password4"]);
        $token = $user->createToken('admin-token', ['super-admin'])->plainTextToken;
        $request_data = [
            "username" => "accc" , 
            "email" => "accc@gmail.com", 
            "password"  => "acccppddd8",
            "confirm_password"  => "acccppddd8"
        ];
        $request = $this->withHeaders([
            "Authorization" => "Bearer " . $token, 
        ])->postJson("/api/register/admin", $request_data);
        $request->assertStatus(201);
        $response_json = [
            'user' =>[
                "name" => 'accc',
                'email' =>'accc@gmail.com',
                "id"=>'5',
            ]
        ];
        $request->assertJson($response_json);
        $request->assertJsonStructure([
            'token',
            'user' => [
                'email',
                'name',
                'id'
            ]
        ]);
    }
}
