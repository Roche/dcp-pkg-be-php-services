<?php

namespace Dcp\Factories;

use Dcp\Contracts\DiscoveryInterface;
use Dcp\Services\Discovery\HashicorpConsul;
use Dcp\Services\Discovery\FileSystemDiscovery;
use Dcp\Exceptions\InvalidClientTypeException;

class DiscoveryFactory
{
    public static function create(?string $discoveryDriver = null): DiscoveryInterface
    {
        $discovery = $discoveryDriver ?? config('dcp.discovery.driver', 'file');

        return match ($discovery) {
            'hashicorp' => new HashicorpConsul(),
            'file' => new FileSystemDiscovery(),
            default => throw new InvalidClientTypeException("Invalid Discovery Interface type"),
        };
    }
}
