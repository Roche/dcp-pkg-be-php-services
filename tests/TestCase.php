<?php

namespace Dcp\Tests;

use Dcp\DcpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Dotenv\Dotenv;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
            $dotenv->load();
        }

        parent::setUp();

        $this->app->make(\Spatie\LaravelData\Support\DataConfig::class);
    }

    protected function getPackageProviders($app): array
    {
        return [
            DcpServiceProvider::class,
            LaravelDataServiceProvider::class
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');

        // Configure DCP settings from environment variables
        $app['config']->set('dcp.internal_gateway_url', env('DCP_INTERNAL_GATEWAY_URL', 'http://localhost'));
        $app['config']->set('dcp.security.verify_ssl', env('DCP_VERIFY_SSL', false));
        $app['config']->set('dcp.environment.client_id', env('DCP_CLIENT_ID', 'test-client'));
        $app['config']->set('dcp.environment.client_secret', env('DCP_CLIENT_SECRET', 'test-secret'));
        $app['config']->set('dcp.environment.jwt_token', env('DCP_JWT_TOKEN', 'test-jwt-secret-key-must-be-long-enough-for-hs256'));
        $app['config']->set('dcp.environment.core_key', env('DCP_CORE_KEY', 'test-key'));
        $app['config']->set('dcp.environment.user_token', env('DCP_USER_TOKEN', null));
        $app['config']->set('dcp.environment.user_id', env('DCP_USER_ID', null));

        // Load endpoint configurations
        $app['config']->set('dcp.endpoints', [
            'core' => include __DIR__ . '/../src/config/dcp/endpoints/core.php',
        ]);
    }
}
