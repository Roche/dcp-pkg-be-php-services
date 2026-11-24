<?php

namespace Dcp\Tests\Unit\Helpers;

use Dcp\Helpers\CurrentUser;
use Dcp\Http\Middleware\DcpAuthenticate;
use Dcp\Models\User;
use Dcp\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;

class CurrentUserTest extends TestCase
{
    #[Test]
    public function it_returns_null_when_no_user_is_authenticated(): void
    {
        Auth::shouldReceive('user')->andReturn(null);

        $this->assertNull(CurrentUser::get());
        $this->assertNull(CurrentUser::id());
    }

    #[Test]
    public function it_returns_the_authenticated_user(): void
    {
        $user = new User();
        $user->id = 1; // Set the ID directly
        $user->email = 'test@example.com'; // Set the email directly

        Auth::shouldReceive('user')->andReturn($user);

        $this->assertEquals($user, CurrentUser::get());
        $this->assertEquals(1, CurrentUser::id());
    }

    #[Test]
    public function platform_admin_does_not_bypass_site_permissions(): void
    {
        $user = new User();
        $user->scopes = [
            (object)[
                'SiteId' => 0,
                'SiteName' => 'GLOBAL',
                'Module' => 'agnostic',
                'Permission' => 'PLATFORMADMIN',
            ],
            (object)[
                'SiteId' => 7,
                'SiteName' => 'Site 7',
                'Module' => 'agnostic',
                'Permission' => 'ADMIN',
            ],
        ];
        Auth::shouldReceive('user')->andReturn($user);

        $this->assertTrue(CurrentUser::isPlatformAdmin());
        $this->assertFalse(CurrentUser::isSiteAdmin(0));
        $this->assertFalse(CurrentUser::isSiteAdmin(3));
        $this->assertTrue(CurrentUser::isSiteAdmin(7));

        $middleware = new class extends DcpAuthenticate {
            public function check(array $requiredScopes, Collection $userScopes, Request $request): bool
            {
                return $this->handleActualUser($requiredScopes, $userScopes, $request);
            }
        };
        $userScopes = collect($user->scopes);

        $this->assertFalse($middleware->check(['SAWVIEWER'], $userScopes, Request::create('/analyses?siteId=3')));
        $this->assertTrue($middleware->check(['SAWVIEWER'], $userScopes, Request::create('/analyses?siteId=0')));
        $this->assertTrue($middleware->check(['SAWVIEWER'], $userScopes, Request::create('/analyses?siteId=7')));
    }
}
