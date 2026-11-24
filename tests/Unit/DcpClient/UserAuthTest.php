<?php

namespace Dcp\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Dcp\Clients\Dcp\DcpClient;
use Dcp\Data\UserData;
use Dcp\Data\UserInfoData;
use Dcp\Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Dcp\Models\User;

class UserAuthTest extends TestCase
{
    protected DcpClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear any existing token
        Storage::fake('local');

        // Skip all tests if user token or user ID is not configured
        if (!config('dcp.environment.user_token') || !config('dcp.environment.user_id')) {
            $this->markTestSkipped('DCP user token or user ID not configured');
        }

        // Create and authenticate a user using Laravel's built-in User model
        $user = new User();
        $user->id = config('dcp.environment.user_id');
        $user->exists = true;

        // Authenticate the user
        $this->actingAs($user);
        request()->headers->set('Authorization', 'Bearer ' . config('dcp.environment.user_token'));

        $this->client = new DcpClient('user');
    }

    #[Test]
    public function it_can_authenticate_with_user_token(): void
    {
        $this->assertNotNull($this->client);
        $this->assertInstanceOf(DcpClient::class, $this->client);
    }

    #[Test]
    public function it_can_fetch_user_info_and_returns_correct_type(): void
    {
        $response = $this->client->getData('core.UserInfo');

        $this->assertNotNull($response);
        $this->assertInstanceOf(UserInfoData::class, $response);
        $this->assertIsInt($response->userId);
        $this->assertIsString($response->unixId);
        $this->assertIsString($response->displayName);
        $this->assertIsIterable($response->sites);
    }

    #[Test]
    public function it_can_query_users_with_filters(): void
    {
        $response = $this->client->getData('core.UserQuery', [
            'SiteId' => 1,
        ]);
        
        $this->assertNotNull($response);
        $this->assertIsIterable($response);
        
        if (!empty($response)) {
            $this->assertInstanceOf(UserData::class, $response[0]);
            $this->assertIsInt($response[0]->userId);
            $this->assertIsString($response[0]->unixId);
        }
    }

    #[Test]
    public function it_can_query_all_users(): void
    {
        $response = $this->client->getData('core.UserQuery');
        
        $this->assertNotNull($response);
        $this->assertIsIterable($response);
    }

    #[Test]
    public function it_validates_user_info_structure(): void
    {
        $userInfo = $this->client->getData('core.UserInfo');
        
        $this->assertInstanceOf(UserInfoData::class, $userInfo);
        $this->assertObjectHasProperty('userId', $userInfo);
        $this->assertObjectHasProperty('email', $userInfo);
        $this->assertObjectHasProperty('unixId', $userInfo);
        $this->assertObjectHasProperty('displayName', $userInfo);
        $this->assertObjectHasProperty('settings', $userInfo);
        $this->assertObjectHasProperty('sites', $userInfo);
    }
}
