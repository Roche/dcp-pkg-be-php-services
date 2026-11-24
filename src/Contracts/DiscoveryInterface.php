<?php

namespace Dcp\Contracts;

interface DiscoveryInterface
{
    public const SHARED_CONFIG_KEYS = ['credential-store'];

    public function registerService(): bool|array;
    public function deregisterService(): bool|array;

    public function getSharedConfig(string $env): array;
    public function getActiveServices(?array $serviceTags = null): array | null;
}
