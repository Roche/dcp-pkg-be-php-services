<?php

namespace Dcp\Services\CredentialStore;

use Dcp\Helpers\Json;
use GuzzleHttp\Client;
use RuntimeException;

use Dcp\Contracts\CredentialStoreInterface;

class HashicorpVault implements CredentialStoreInterface
{
    protected array $endpoints = [
        'login'     => 'v1/auth/userpass/login/:username',
        'certlogin' => 'v1/auth/cert/login',
        'secret'    => 'v1/:path',
    ];

    protected Client $client;
    protected ?string $token = null;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('dcp.credential_store.hashicorp.base_uri'),
            'verify'   => false,
        ]);

        match (config('dcp.credential_store.hashicorp.driver')) {
            'certificate' => $this->certlogin(),
            'credentials' => $this->login(),
            default => throw new \Exception("Invalid driver type"),
        };
    }

    protected function certlogin(): HashicorpVault
    {
        $certPath = config('dcp.credential_store.hashicorp.cert_path');

        if (!empty($certPath) && !preg_match('/^(\/|[A-Za-z]:\\\\)/', $certPath)) {
            $certPath = storage_path($certPath);
        }

        if (!file_exists($certPath)) {
            throw new RuntimeException('Hashicorp certificate not found.');
        }

        $url = $this->endpoints['certlogin'];
        $response = $this->client->request('PUT', $url, [
            'headers' => [
                'X-Vault-Namespace' => config('dcp.credential_store.hashicorp.namespace'),
                'X-Vault-Request'   => true,
            ],
            'json'    => (object)[
                'name' => '',
            ],
            'cert'    => $certPath,
            'ssl_key' => $certPath,
        ]);

        $responseData = Json::decode($response->getBody());

        $this->token = $responseData->auth->client_token;

        return $this;
    }

    protected function login(): HashicorpVault
    {
        $url = $this->endpoints['login'];
        $url = str_replace(':username', config('dcp.credential_store.hashicorp.username'), $url);

        $response = $this->client->request('PUT', $url, [
            'headers' => [
                'X-Vault-Namespace' => config('dcp.credential_store.hashicorp.namespace'),
                'X-Vault-Request'   => true,
            ],
            'json'    => (object)[
                'password' => config('dcp.credential_store.hashicorp.password'),
            ],
        ]);

        $responseData = Json::decode($response->getBody());
        $this->token = $responseData->auth->client_token;

        return $this;
    }

    public function getSecret(string $path): string|array
    {
        $url = $this->endpoints['secret'];
        $url = str_replace(':path', $path, $url);

        $response = $this->client->request('GET', $url, [
            'headers' => [
                'X-Vault-Token'     => $this->token,
                'X-Vault-Namespace' => config('dcp.credential_store.hashicorp.namespace'),
                'X-Vault-Request'   => true,
            ],
        ]);

        $responseData = Json::decode($response->getBody());

        return (array)$responseData->data->data;
    }

    public function setSecret(string $key, string $value): bool
    {
        return false;
    }

    public function removeSecret(string $key): bool
    {
        return false;
    }
}
