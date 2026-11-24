<?php

namespace Dcp\Clients\Calculation;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Dcp\Contracts\CalculationInterface;
use Dcp\Data\DataSourceData;
use Dcp\Traits\StoresResponseInterface;

abstract class BaseRestClient extends Client implements CalculationInterface
{
    use StoresResponseInterface;

    protected ?bool $validated = null;
    protected array|string|null $auth = null;
    protected ?string $baseUrl = null;
    protected ?string $path = null;

    public function __construct(DataSourceData $dataSourceData)
    {
        $this->setValidated($dataSourceData->isValidated);

        $this->setup($dataSourceData->connectionString);

        parent::__construct([
            'base_uri' => $this->baseUrl . $this->path
        ]);
    }

    abstract protected function getDebugMessage(ClientException $exception): string;

    public function setValidated(?bool $validated): void
    {
        $this->validated = $validated;
    }

    public function isValidated(): ?bool
    {
        return $this->validated;
    }

    public function post($uri, array $options = []): ResponseInterface
    {
        $options = $this->manageOptions($options);

        try {
            $this->responseData = parent::post($uri, $options);
        } catch (ClientException $e) {
            if ($e->hasResponse() && app()->isLocal()) {
                $debugMessage = $this->getDebugMessage($e);

                throw new \Exception($debugMessage);
            }
        }

        return $this->responseData;
    }

    public function get($uri, array $options = []): ResponseInterface
    {
        $options = $this->manageOptions($options);

        try {
            $this->responseData = parent::get($uri, $options);
        } catch (ClientException $e) {
            if ($e->hasResponse() && app()->isLocal()) {
                $debugMessage = $this->getDebugMessage($e);

                throw new \Exception($debugMessage);
            }
        }

        return $this->responseData;
    }

    public function postAsync($uri, array $options = []): PromiseInterface
    {
        $options = $this->manageOptions($options);

        return parent::postAsync($uri, $options);
    }

    public function getAsync($uri, array $options = []): PromiseInterface
    {
        $options = $this->manageOptions($options);

        return parent::getAsync($uri, $options);
    }

    abstract protected function manageOptions(array $options): array;

    abstract public function setup(array $credentials): void;
}
