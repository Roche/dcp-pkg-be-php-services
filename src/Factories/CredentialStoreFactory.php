<?php

namespace Dcp\Factories;

use Dcp\Contracts\CredentialStoreInterface;
use Dcp\Services\CredentialStore\HashicorpVault;
use Dcp\Services\CredentialStore\FileSystemCredentialStore;
use Dcp\Exceptions\InvalidClientTypeException;

class CredentialStoreFactory
{
    public static function create(?string $storeDriver = null): CredentialStoreInterface
    {
        $store = $storeDriver ?? config('dcp.credential_store.driver');
        return match ($store) {
            'hashicorp' => new HashicorpVault(),
            'file' => new FileSystemCredentialStore(),
            default => throw new InvalidClientTypeException("Invalid message Credential Store"),
        };
    }
}
