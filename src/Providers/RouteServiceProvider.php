<?php

namespace Dcp\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::group([
            'middleware' => 'api',
            'prefix' => 'api',
            'namespace' => 'Dcp\Http\Controllers',
        ], function () {
            require_once __DIR__.'/../routes/api.php';
        });
    }
}
