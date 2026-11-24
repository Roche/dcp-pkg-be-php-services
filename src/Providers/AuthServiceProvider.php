<?php

namespace Dcp\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Dcp\Guard\JwtBearerGuard;
use Dcp\Http\Middleware\DcpMicroserviceAuthenticate;
use Illuminate\Routing\Router;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->booted(function () {
            Config::set('auth.defaults.guard', 'jwt-bearer');
            Config::set('auth.guards.jwt-bearer', [
                'driver' => 'jwt-bearer',
                'provider' => 'users',
            ]);
            Config::set('auth.providers.users', [
                'driver' => 'eloquent',
                'model' => \Dcp\Models\User::class,
            ]);
        });
    }

    public function boot(Router $router): void
    {
        $router->aliasMiddleware('dcp.auth', \Dcp\Http\Middleware\DcpAuthenticate::class);
        $router->aliasMiddleware('dcp.microservice.auth', DcpMicroserviceAuthenticate::class);

        Auth::extend('jwt-bearer', function ($app) {
            return new JwtBearerGuard($app['request']);
        });
    }
}
