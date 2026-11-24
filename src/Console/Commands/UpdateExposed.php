<?php

namespace Dcp\Console\Commands;

use Dcp\Helpers\Json;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use JsonException;

class UpdateExposed extends Command
{
    protected const DEFAULT_PORT = 80;
    protected const DEFAULT_RATE_LIMIT = 50;
    protected const DEFAULT_PERIOD = '1s';
    protected const DEFAULT_PERIOD_TIMESPAN = 1;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dcp:update-exposed {env=dev} {--service-discovery}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates (or creates) json file for Ocelot to make endpoints accessible via API';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws JsonException
     * @throws JsonException
     */
    public function handle(): int
    {
        $exposed = $this->initializeExposed();
        $allApiRoutes = $this->getApiRoutes();

        foreach ($allApiRoutes as $apiRoute) {
            $tempEndpoint = $this->createEndpoint($apiRoute);
            $exposed->Routes[] = $tempEndpoint;
        }

        $this->saveToJson($exposed);

        return COMMAND::SUCCESS;
    }

    private function initializeExposed(): object
    {
        return (object) [
            'Routes' => [],
        ];
    }

    private function getApiRoutes(): array
    {
        $allRoutes = Route::getRoutes()->get();

        return array_values(array_filter($allRoutes, static function ($el) {
            return str_starts_with($el->uri, 'api/') && property_exists($el, 'isExposed');
        }));
    }

    private function createEndpoint($apiRoute): object
    {
        $endpointTemplate = $this->getEndpointTemplate();

        $endpoint = clone $endpointTemplate;
        $endpoint->DownstreamPathTemplate = '/' . $apiRoute->uri;
        $endpoint->UpstreamPathTemplate = '/' . str_replace('api/', config('dcp.module_slug') . '/', $apiRoute->uri);
        $endpoint->UpstreamHttpMethod = array_merge(
            array_filter(
                $apiRoute->methods,
                static fn($method) => $method !== 'HEAD',
            ),
            ['OPTIONS'],
        );
        $endpoint->SwaggerKey = strtoupper(config('dcp.module_slug'));

        return $endpoint;
    }

    private function getEndpointTemplate(): object
    {
        $url = config('app.url');
        $port = parse_url($url, PHP_URL_PORT) ?? self::DEFAULT_PORT;
        $cleanUrl = preg_replace('~:\d+(?=/|$)~', '', $url);

        if ($this->option('service-discovery')) {
            $object = [
                'ServiceName' => config('dcp.discovery.service.name'),
            ];
        } else {
            $object = [
                'DownstreamHostAndPorts' => [
                    (object) [
                        'Host' => str_replace(['https://', 'http://'], '', $cleanUrl),
                        'Port' => $port,
                    ],
                ],
            ];
        }

        $object = array_merge($object, [
            'DownstreamPathTemplate'  => '',
            'DownstreamScheme'        => 'https',
            'UpstreamHeaderTransform' => (object) [
                'X-DCPGateway-Key' => config('dcp.environment.gateway_key'),
            ],
            'RateLimitOptions'        => (object) [
                'ClientWhitelist'    => [],
                'EnableRateLimiting' => true,
                'Period'             => self::DEFAULT_PERIOD,
                'PeriodTimespan'     => self::DEFAULT_PERIOD_TIMESPAN,
                'Limit'              => self::DEFAULT_RATE_LIMIT,
            ],
            'UpstreamPathTemplate'    => '',
            'UpstreamHttpMethod'      => [],
            'SwaggerKey'              => '',
            'AuthenticationOptions'   => (object) [
                'AuthenticationProviderKey' => 'ApiGateWaySchema',
                'AllowedScopes'             => [],
            ],
        ]);

        return (object) $object;
    }

    private function saveToJson($exposed): void
    {
        Storage::disk('local')->put(
            'ocelot.' . strtoupper(config('dcp.module_slug')) . '.json',
            Json::encode($exposed, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES),
        );
    }
}
