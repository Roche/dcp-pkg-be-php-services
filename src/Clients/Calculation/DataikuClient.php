<?php

namespace Dcp\Clients\Calculation;

use GuzzleHttp\Exception\ClientException;
use Exception;
use Psr\Http\Message\ResponseInterface;

class DataikuClient extends BaseRestClient
{
    public function setup(array $connectionString): void
    {
        $this->auth = [
            $connectionString['Token'],
            ''
        ];
        $this->baseUrl = $connectionString['BaseURL'];
        $this->path = $connectionString['ProjectPath'];
    }

    protected function manageOptions(array $options): array
    {
        if (empty($options['auth'])) {
            $options['auth'] = $this->auth;
        }

        if (empty($options['verify'])) {
            $options['verify'] = false;
        }

        if (empty($options['headers'])) {
            $options['headers'] = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];
        }

        return $options;
    }

    public function processRequestData(array $data, string $dataType = 'multipart'): array
    {
        if (!in_array($dataType, ['json', 'multipart', 'form_params', 'body'])) {
            throw new Exception($dataType . ' not supported by Client.');
        }

        return [
            $dataType => $data
        ];
    }

    public function post($uri, array $options = []): ResponseInterface
    {
        $this->responseData = parent::post($uri, $options);

        return $this->responseData;
    }

    public function get($uri, array $options = []): ResponseInterface
    {
        $this->responseData = parent::get($uri, $options);

        return $this->responseData;
    }

    protected function getDebugMessage(ClientException $exception): string
    {
        return $exception->getMessage();
    }
}
