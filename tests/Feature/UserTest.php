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

    private  $user_1;
    private  $token_1;

    public function setUp():void
    {
        parent::setUp();
        $create_users = HelperTest::create_users();
        $this->user_1 = $create_users['users'][0];
        $this->token_1 = $create_users['tokens'][0];
    }

    public function test_admin_register_unauthorized(): void
    {
        $request = $this->postJson("/api/register/admin");
        $request->assertUnauthorized();
    }

    public function test_admin_register_wrong_ability():void
    {   
        $headers = ['Authorization' => 'Bearer ' .$this->token_1];
        $request = $this->postJson("/api/register/admin",[],$headers);
        $request->assertStatus(403);
    }

    public function test_admin_register_accepted():void 
    {
        $user = User::create(['name' => "aaaaf" , 'email' =>"aaaaf@gmail.com" , "password" =>"password4"]);
        $token = $user->createToken('admin-token', ['super-admin'])->plainTextToken;

        $request_data = [
            "username" => "accc" , 
            "email" => "accc@gmail.com", 
            "password"  => "acccppddd8",
            "confirm_password"  => "acccppddd8"
        ];
        $headers = ["Authorization" => "Bearer " . $token];

        $request = $this->postJson("/api/register/admin",$request_data,$headers);
        $request->assertStatus(201);
        $response_json = [
            'user' =>[
                "name" => "accc",
                'email' =>"accc@gmail.com", 
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
