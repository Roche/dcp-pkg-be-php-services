<?php

namespace Dcp\Services\CredentialStore;

use Illuminate\Support\Facades\Storage;
use Dcp\Helpers\Json;
use Dcp\Contracts\CredentialStoreInterface;
use Dcp\Exceptions\CredentialNotFoundException;

class FileSystemCredentialStore implements CredentialStoreInterface
{
    protected string $filePath;

    public function __construct()
    {
        $this->filePath = config('dcp.credential_store.file');

        if (!Storage::exists($this->filePath)) {
            // Initialize with an empty JSON object if the file doesn't exist
            Storage::put($this->filePath, Json::encode((object) [], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Retrieves a secret by key.
     */
    public function getSecret(string $key): array|string
    {
        $credentials = $this->loadCredentials();

        if (!array_key_exists($key, $credentials)) {
            throw new CredentialNotFoundException($key);
        }

        return $credentials[$key];
    }

    /**
     * Stores a secret key-value pair.
     */
    public function setSecret(string $key, string $value): bool
    {
        $credentials = $this->loadCredentials();
        $credentials[$key] = $value;

        return $this->saveCredentials($credentials);
    }

    /**
     * Removes a secret by key.
     */
    public function removeSecret(string $key): bool
    {
        $credentials = $this->loadCredentials();

        if (isset($credentials[$key])) {
            unset($credentials[$key]);
            return $this->saveCredentials($credentials);
        }

        return false;
    }

    /**
     * Loads and decodes the credentials JSON file.
     */
    protected function loadCredentials(): array
    {
        $data = Storage::get($this->filePath);
        return Json::decode($data, true) ?? [];
    }

    /**
     * Saves the credentials array as JSON to the storage file.
     */
    protected function saveCredentials(array $credentials): bool
    {
        return (bool) Storage::put($this->filePath, Json::encode($credentials, JSON_PRETTY_PRINT));
    }
}
