<?php

namespace Dcp\Contracts;

interface CredentialStoreInterface
{
    public function getSecret(string $key): string|array;
    public function setSecret(string $key, string $value): bool;
    public function removeSecret(string $key): bool;
}
