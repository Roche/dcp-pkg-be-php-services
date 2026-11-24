<?php

namespace Dcp\Clients\Calculation;

use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
use Exception;

class OpenCpuClient extends BaseRestClient
{
    public function setup(array $connectionString): void
    {
        $this->auth = [
            $connectionString['RUser'],
            $connectionString['RPassword']
        ];

        $this->baseUrl = $connectionString['RserverBaseURL'];
        $this->path = $connectionString['RserverLibPath'];
    }

    protected function manageOptions(array $options): array
    {
        if (empty($options['auth'])) {
            $options['auth'] = $this->auth;
        }

        if (!array_key_exists('verify', $options)) {
            $options['verify'] = false;
        }

        return $options;
    }

    public function processRequestData(array $data, string $dataType = 'multipart'): array
    {
        if (!in_array($dataType, ['json', 'multipart', 'form_params', 'body'])) {
            throw new Exception($dataType . ' not supported by Client.');
        }

        $multipartData = [];

        foreach ($data as $key => $value) {
            $multipartData[] = [
                'name' => $key,
                'contents' => match ($value['type']) {
                    'json' => json_encode($value['value']),
                    'string' => '"' . $value['value'] . '"',
                    default => $value['value']
                }
            ];
        }

        return [
            $dataType => $multipartData
        ];
    }

    public function post($uri, array $options = []): ResponseInterface
    {
        $uri = $this->updateJsonUri($uri);
        return parent::post($uri, $options);
    }

    public function get($uri, array $options = []): ResponseInterface
    {
        $uri = $this->updateJsonUri($uri);
        return parent::get($uri, $options);
    }

    protected function updateJsonUri($uri): string
    {
        if (preg_match('/\/json(\?.*)?$/', $uri)) {
            $parsedUrl = parse_url($uri);
            $queryParams = [];

            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
            }

            $queryParams['na'] = $queryParams['na'] ?? 'string';
            $queryParams['digits'] = $queryParams['digits'] ?? 7;

            $baseUri = strtok($uri, '?');
            return $baseUri . '?' . http_build_query($queryParams);
        }

        return $uri;
    }

    public function getResponseSession(): string
    {
        return $this->responseData->getHeader('X-ocpu-session')[0];
    }

    protected function getDebugMessage(ClientException $exception): string
    {
        $headers = $exception->getResponse()->getHeaders();

        if (isset($headers['Location'])) {
            return $exception->getMessage() . ' - Debug info at: ' . $headers['Location'][0];
        }

        return $exception->getMessage();
    }
}
