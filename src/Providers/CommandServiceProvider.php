<?php

namespace Dcp\Providers;

use Illuminate\Support\ServiceProvider;

use Dcp\Console\Commands\ServiceRegistration;
use Dcp\Console\Commands\MakeDcpWorker;
use Dcp\Console\Commands\UpdateExposed;
use Dcp\Console\Commands\ManageEnv;
use Dcp\Console\Commands\GenerateToken;

class CommandServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ServiceRegistration::class,
                MakeDcpWorker::class,
                UpdateExposed::class,
                ManageEnv::class,
                GenerateToken::class
            ]);
        }
    }
}
