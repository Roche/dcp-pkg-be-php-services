<?php

namespace Dcp\Providers;

use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge main dcp config at root level
        $this->mergeConfigFrom(__DIR__ . '/../config/dcp.php', 'dcp');

        // Merge message-enum separately
        $this->mergeConfigFrom(__DIR__ . '/../config/message-enum.php', 'message-enum');

        // Merge core endpoints
        $this->mergeConfigFrom(__DIR__ . '/../config/dcp/endpoints/core.php', 'dcp.endpoints.core');

        // Allow app to add more endpoint files
        $appEndpointsPath = config_path('dcp/endpoints');
        if (is_dir($appEndpointsPath)) {
            foreach (glob($appEndpointsPath . '/*.php') as $file) {
                $filename = basename($file, '.php');
                $this->mergeConfigFrom($file, "dcp.endpoints.$filename");
            }
        }
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/dcp' => config_path('dcp'),
            __DIR__ . '/../config/dcp.php' => config_path('dcp.php'),
            __DIR__ . '/../config/message-enum.php' => config_path('message-enum.php')
        ], 'dcp-config');
    }
}
