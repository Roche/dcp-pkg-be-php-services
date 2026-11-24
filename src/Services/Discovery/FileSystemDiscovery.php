<?php

namespace Dcp\Services\Discovery;

use Dcp\Contracts\DiscoveryInterface;
use Dcp\Helpers\Json;
use Dcp\Traits\FormatsDiscoveryMetaData;

use Illuminate\Support\Facades\Storage;

class FileSystemDiscovery implements DiscoveryInterface
{
    use FormatsDiscoveryMetaData;

    protected $serviceConfig;
    protected string $serviceFilePath;
    protected string $kvFilePath;

    public function __construct()
    {
        $this->serviceConfig = config('dcp.discovery.service');
        $this->serviceFilePath = str_replace('.json', '.services.json', config('dcp.discovery.file'));
        $this->kvFilePath = str_replace('.json', '.kv.json', config('dcp.discovery.file'));

        if (!Storage::exists($this->serviceFilePath)) {
            Storage::put($this->serviceFilePath, JSON::encode([]));
        }

        if (!Storage::exists($this->kvFilePath)) {
            Storage::put($this->kvFilePath, JSON::encode([]));
        }
    }

    public function registerService(): bool|array
    {
        try {
            $services = $this->getAllServices();
            $services[$this->serviceConfig['id']] = $this->getFormatedService();

            Storage::put($this->serviceFilePath, JSON::encode($services));
            return true;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function deregisterService(): bool|array
    {
        try {
            $services = $this->getAllServices();

            if (isset($services[$this->serviceConfig['id']])) {
                unset($services[$this->serviceConfig['id']]);
                Storage::put($this->serviceFilePath, JSON::encode($services));
                return true;
            }

            return [
                'success' => false,
                'message' => 'Service not found'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getSharedConfig(string $env): array
    {
        $sharedConfig = [];
        try {
            $content = Storage::get($this->kvFilePath);
            $sharedConfig = JSON::decode($content, true) ?? [];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return $sharedConfig;
    }

    public function getActiveServices(?array $serviceTags = null): array | null
    {
        try {
            $allServices = $this->getAllServices();

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
                'message' => $e->getMessage()
            ];
        }
    }

    protected function getAllServices(): array
    {
        $content = Storage::get($this->serviceFilePath);
        return JSON::decode($content, true) ?? [];
    }
}
