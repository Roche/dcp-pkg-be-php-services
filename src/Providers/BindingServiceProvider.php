<?php

namespace Dcp\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

use Dcp\Models\User;
use Dcp\Contracts\CredentialStoreInterface;
use Dcp\Contracts\DiscoveryInterface;
use Dcp\Contracts\MessageQueueInterface;

use Dcp\Factories\CredentialStoreFactory;
use Dcp\Factories\MessageQueueFactory;
use Dcp\Factories\DiscoveryFactory;

class BindingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('ValidationStatus', function () {
            return new \Dcp\Validation\ValidationStatus();
        });

        $this->app->singleton(CredentialStoreInterface::class, function () {
            return CredentialStoreFactory::create();
        });

        $this->app->singleton(DiscoveryInterface::class, function () {
            return DiscoveryFactory::create();
        });

        $this->app->singleton(MessageQueueInterface::class, function () {
            return MessageQueueFactory::create();
        });

        $this->app->bind(UserContract::class, User::class);
    }
}
