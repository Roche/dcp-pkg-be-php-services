<?php

namespace Dcp\Factories;

use Dcp\Contracts\DcpInterface;
use Dcp\Clients\Dcp\DcpClient;
use Dcp\Clients\Dcp\MockClient;
use Dcp\Exceptions\InvalidClientTypeException;

class DcpClientFactory
{
    public static function create(?string $authMethod = null): DcpInterface
    {
        $dcpDriver = config('dcp.environment.client_driver');
        return match ($dcpDriver) {
            'gateway' => new DcpClient($authMethod),
            'mock' => new MockClient($authMethod),
            default => throw new InvalidClientTypeException($dcpDriver),
        };
    }
}
