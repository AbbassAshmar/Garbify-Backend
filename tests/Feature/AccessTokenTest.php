<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Enums\TokenAbility;
use Carbon\Carbon;
use Database\Seeders\UserRolePermissionSeeder;

class AccessTokenTest extends TestCase
{
    use RefreshDatabase;

    protected $user_1;
    protected $refresh_token_1; // valid
    protected $refresh_token_2; // expired
    protected $access_token_1;

    public function setUp():void
    {
        parent::setUp();
        $this->seed(UserRolePermissionSeeder::class);

        $user_token =  HelperTest::create_users();
        $this->user_1 = $user_token['users'][0];
        $this->access_token_1 =  $user_token['tokens'][0];
        $this->refresh_token_1 =$this->user_1->createToken("refresh_token",[TokenAbility::ISSUE_ACCESS_TOKEN->value],Carbon::now()->addDays(1))->plainTextToken;

        $refresh_token_2 =$this->user_1->createToken("refresh_token",[TokenAbility::ISSUE_ACCESS_TOKEN->value],Carbon::now()->subDays(3));
        $refresh_token_2->accessToken->created_at = Carbon::now()->subDays(5);
        $refresh_token_2->accessToken->last_used_at = Carbon::now()->subDays(4);
        $refresh_token_2->accessToken->save();

        $this->refresh_token_2= $refresh_token_2->plainTextToken;
    }

    public function test_create_access_token_by_refresh_token_success(): void
    {
        $headers = $this->transformHeadersToServerVars(['CONTENT_TYPE' => 'application/json', 'Accept' => 'application/json',]);
        $cookies = ['refresh_token' => $this->refresh_token_1];
        $response = $this->call('POST', '/api/access_tokens', [],$cookies,[], $headers);
        $response->assertCreated();
        $response->assertJsonStructure([
            'status',
            'error',
            'data'=>['token'],
            'metadata'
        ]);
        $response->assertJsonFragment(['status'=>'success']);
    }

    public function test_create_access_token_by_refresh_token_missing(): void
    {
        $headers = $this->transformHeadersToServerVars(['CONTENT_TYPE' => 'application/json', 'Accept' => 'application/json',]);
        
        $cookies = [];
        $response = $this->call('POST', '/api/access_tokens', [],$cookies, [], $headers);
        $response->assertUnauthorized();
        $response->assertJsonFragment(['message' =>"Unauthenticated."]);
    }
    public function test_create_access_token_by_access_api_ability(): void
    {
        $headers = $this->transformHeadersToServerVars(['CONTENT_TYPE' => 'application/json', 'Accept' => 'application/json',]);
        $cookies = ['refresh_token' => $this->access_token_1]; //access_token instead of refresh_token
        $response = $this->call('POST', '/api/access_tokens', [],$cookies, [], $headers);
        $response->assertForbidden();
        $error = [
            "message"=>'Invalid ability provided.',
            'code'=>403
        ];
        $response_body = HelperTest::getFailedResponse($error,null);
        $response->assertJson($response_body);
    }
    public function test_create_access_token_by_expired_refresh_token(): void
    {
        $headers = $this->transformHeadersToServerVars(['CONTENT_TYPE' => 'application/json', 'Accept' => 'application/json',]);
        $cookies = ['refresh_token' => $this->refresh_token_2]; //refresh_token_2 expired
        $response = $this->call('POST', '/api/access_tokens', [],$cookies, [], $headers);
        $response->assertUnauthorized();
        $response->assertJsonFragment(['message' =>"Unauthenticated."]);
    }
    public function test_create_access_token_by_invalid_refresh_token(): void
    {
        $headers = $this->transformHeadersToServerVars(['CONTENT_TYPE' => 'application/json', 'Accept' => 'application/json',]);
        $cookies = ['refresh_token' => '6|ajsfiow34aslkdfj2432j4jf9ds0'];
        $response = $this->call('POST', '/api/access_tokens', [],$cookies, [], $headers);
        $response->assertUnauthorized();
        $response->assertJsonFragment(['message' =>"Unauthenticated."]);
    }
    public function test_create_access_token_by_old_unexpired_refresh_token(): void
    {
        $headers = $this->transformHeadersToServerVars(['CONTENT_TYPE' => 'application/json', 'Accept' => 'application/json',]);
        $cookies = ['refresh_token' => $this->refresh_token_1];
        $response = $this->call('POST', '/api/access_tokens', [],$cookies,[], $headers);
        $response->assertCreated();
        
        $cookies = ['refresh_token' => $this->refresh_token_1];
        $response = $this->call('POST', '/api/access_tokens', [],$cookies,[], $headers);
        $response->assertUnauthorized();
        $response->assertJsonFragment(['message'=>'Unauthenticated.']);
    }
}
