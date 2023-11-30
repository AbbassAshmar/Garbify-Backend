<?php

namespace Tests\Feature;

use App\Models\FavoritesList;
use App\Models\User;
use Database\Seeders\UserRolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
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
        $this->seed(UserRolePermissionSeeder::class);
        $create_users = HelperTest::create_users();
        $this->user_1 = $create_users['users'][0];
        $this->token_1 = $create_users['tokens'][0];
    }

    public function test_register():void
    {
        $this->assertEmpty(FavoritesList::all());
        $data = [
            "username" => "test" , 
            "email" => "test@gmail.com", 
            "password"  => "acccppddd8",
            "confirm_password"  => "acccppddd8"
        ];
        $request = $this->postJson("/api/register",$data);
        $request->assertCreated();
        $request->assertJsonStructure([
            'data' => ['user', 'token'],
            'error',
            'status',
            'metadata'
        ]);

        //assert the creation of a favorites list for the new user
        $user_id = $request->json()['data']['user']['id'];
        $favoritesList = FavoritesList::where("user_id",$user_id)->first();
        $this->assertNotNull($favoritesList);
    }

    public function test_register_email_already_used():void
    {
        $data = [
            "username" => "test" , 
            "email" => "User2@gmail.com", 
            "password"  => "acccppddd8",
            "confirm_password"  => "acccppddd8"
        ];
        $request = $this->postJson("/api/register",$data);
        $request->assertBadRequest();
        $error = [
            'message' => "Validation error.",
            'code' => 400,
            'details' => ['email' => ['The email has already been taken.']]
        ];
        $metadata = ["error_fields"=>["email"]];
        $response = HelperTest::getFailedResponse($error,$metadata);
        $request->assertJson($response);
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
        $request->assertForbidden();
        $request->assertJsonStructure([
            'data',
            'error',
            'status',
            'metadata'
        ]);
    }

    public function test_admin_register_accepted():void 
    {
        $superAdminTokenArr = HelperTest::create_super_admin();
        $superAdminToken = $superAdminTokenArr['token'];

        $headers = ["Authorization" => "Bearer " . $superAdminToken];
        $request_data = [
            "username" => "accc" , 
            "email" => "accc@gmail.com", 
            "password"  => "acccppddd8",
            "confirm_password"  => "acccppddd8"
        ];

        $request = $this->postJson("/api/register/admin",$request_data,$headers);
        $request->assertStatus(201);
        $request->assertJsonFragment(['email' =>"accc@gmail.com",]);
        $request->assertJsonStructure([
            'error',
            'metadata',
            'status',
            'data'=>[
                'token',
                'user' => [
                    'email',
                    'name',
                    'id'
                ]
            ]
        ]);
    }

    public function test_update_user():void
    {
        $headers = ["Authorization" => "Bearer ".$this->token_1];
        $body = [
            "name" => "new_name",
            "email" => "new_email@gmail.com"
        ];
        $request = $this->patchJson("/api/users/user",$body, $headers);
        $request->assertOk();
        $this->user_1->refresh();
        $data = ['user'=>['email'=>'new_email@gmail.com', 'name'=>'new_name']];
        $response = HelperTest::getSuccessResponse($data,null);
        $request->assertJson($response);
        $this->assertEquals($this->user_1->name , 'new_name');
    }

    public function test_update_favorites_list_no_fields_to_update():void
    {
        $headers = ["Authorization" => "Bearer " . $this->token_1]; 
        $data = [];
        $request = $this->patchJson("/api/users/user",$data, $headers);
        $request->assertJson(HelperTest::getSuccessResponse(null, null));
        $request->assertOk();
    }

    public function test_update_user_email_already_used():void
    {
        $headers = ["Authorization" => "Bearer ".$this->token_1];
        $body = [
            "name" => "new_name",
            "email" => "User2@gmail.com@gmail.com" // already used by user_2
        ];

        $request = $this->patchJson("/api/users/user",$body, $headers);
        $request->assertBadRequest();

        $error = [
            'message' => 'Validation error.',
            'code'=>400,
            'details' => ['email' => 'The email has already been taken.']
        ];
        $metadata =['error_fields' => ["email"]];
        $response = HelperTest::getSuccessResponse($error, $metadata);
        $request->assertJson($response);

        $this->user_1->refresh();
        $this->assertNotEquals("new_name", $this->user_1->name);
    }

    public function test_update_user_field_unauthorized_to_be_updated():void
    {
        $headers = ["Authorization" => "Bearer ".$this->token_1];
        $body = [
            "name" => "new_name",
            'id'=>324
        ];

        $request = $this->patchJson("/api/users/user",$body, $headers);
        $error = [
            "message"=>"Validation error.",
            "code" => 400,
            "details"=> [
                "id"=> [
                    "You do not have the required authorization to update this field."
                ]
            ]
        ];
        $metadata  = ["error_fields"=>['id']];
        $request->assertJson(HelperTest::getFailedResponse($error,$metadata));

        $this->user_1->refresh();
        $this->assertNotEquals("new_name", $this->user_1->name);
    }

    public function test_update_user_name_and_profile_picture():void
    {   
        // default profile picture initially
        $this->assertTrue(str_ends_with($this->user_1->profile_picture,'defaultUserProfilePicture.jpg')); 
        
        // Create a fake image file
        $fakeImage = UploadedFile::fake()->image('test_image.jpg');

        $headers = ["Authorization" => "Bearer " . $this->token_1]; 
        $body = ['name' => 'new_name' , 'thumbnail' =>$fakeImage];

        $request = $this->patchJson("/api/users/user",$body, $headers);
        $request->assertOk();

       
        $expected_json_struc = [
            'status',
            'data' => [
                'user' =>['name', 'profile_picture']
            ],
            'metadata',
            'error',
        ];
        $request->assertJsonStructure($expected_json_struc);
        $request->assertJsonFragment(["name"=>"new_name"]);
        
        // /public/storage/usersProfilePictures points to /storage/app/public/usersProfilePictures
        $expected_pfp = env('APP_URL') . '/storage/usersProfilePictures/'; // /public not required to be in url
        $returned_pfp =$request->json()['data']['user']['profile_picture'];
        $this->assertTrue(strpos($returned_pfp,$expected_pfp) === 0);
    }
}
