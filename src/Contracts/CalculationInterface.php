<?php

namespace Dcp\Contracts;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Dcp\Data\DataSourceData;

interface CalculationInterface
{
    public function __construct(DataSourceData $dataSourceData);
    
    public function isValidated(): ?bool;
    public function setValidated(?bool $validated): void;

    public function post($url, array $postData = []): ResponseInterface;
    public function get($url, array $postData = []): ResponseInterface;
    public function postAsync($url, array $postData = []): PromiseInterface;
    public function getAsync($url, array $postData = []): PromiseInterface;
}
