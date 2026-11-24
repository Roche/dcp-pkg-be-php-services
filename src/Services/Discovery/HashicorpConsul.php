<?php

namespace Dcp\Services\Discovery;

use Dcp\Helpers\Json;
use Dcp\Contracts\DiscoveryInterface;
use Dcp\Traits\FormatsDiscoveryMetaData;
use Consul\Client;
use Consul\Services\Agent;
use Consul\Services\KV;

class HashicorpConsul implements DiscoveryInterface
{
    use FormatsDiscoveryMetaData;

    protected Client $client;
    protected Agent $agent;
    protected KV $kv;
    protected array $serviceConfig;

    public function __construct()
    {
        $this->serviceConfig = config('dcp.discovery.service');

        $this->client = new Client([
            'base_uri' => config('dcp.discovery.consul.url'),
            'verify_peer' => config('dcp.discovery.consul.verify_peer'),
            'verify_host' => config('dcp.discovery.consul.verify_host'),
            'headers'  => [
                'X-Consul-Token' => config('dcp.discovery.consul.acl_token'),
            ]
        ]);

        $this->agent = new Agent($this->client);
        $this->kv = new KV($this->client);
    }

    /**
     * Registers the service with Consul.
     */
    public function registerService(): bool|array
    {
        try {
            $this->agent->registerService($this->getFormatedService());

            return true;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Deregisters the service from Consul.
     */
    public function deregisterService(): bool|array
    {
        try {
            $this->agent->deregisterService($this->serviceConfig['id']);
            return true;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves shared configuration from Consul's key-value store.
     */
    public function getSharedConfig(string $env): array
    {
        $sharedConfig = [];
        try {
            foreach (self::SHARED_CONFIG_KEYS as $key) {
                $kvResponse = JSON::decode($this->kv->get($env . '/' . $key)->getBody(), true);
                $sharedConfig[$key] = JSON::decode(base64_decode($kvResponse[0]['Value']), true);
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return $sharedConfig;
    }

    /**
     * Retrieves a list of active services registered with Consul.
     */
    public function getActiveServices(?array $serviceTags = null): array | null
    {
        try {
            $allServices = JSON::decode($this->agent->services()->getBody(), true);

            if (is_null($serviceTags)) {
                return $allServices;
            }

            $filteredServices = array_filter($allServices, function ($service) use ($serviceTags) {
                if (!isset($service['Tags']) || !is_array($service['Tags'])) {
                    return false;
                }
                return empty(array_diff($serviceTags, $service['Tags']));
            });

            return $filteredServices ?: null;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
