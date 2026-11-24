<?php

namespace Dcp;

use Illuminate\Support\ServiceProvider;

class DcpServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->register(\Dcp\Providers\BindingServiceProvider::class);
        $this->app->register(\Dcp\Providers\ConfigServiceProvider::class);
        $this->app->register(\Dcp\Providers\AuthServiceProvider::class);
        $this->app->register(\Dcp\Providers\MacroServiceProvider::class);
        $this->app->register(\Dcp\Providers\CommandServiceProvider::class);
        $this->app->register(\Dcp\Providers\RouteServiceProvider::class);
    }

    public function boot()
    {
        // Load helper functions
        require_once __DIR__ . '/Helpers/Helpers.php';
    }
}