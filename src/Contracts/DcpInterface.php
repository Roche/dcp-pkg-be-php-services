<?php

namespace Dcp\Contracts;

use GuzzleHttp\Promise\PromiseInterface;

interface DcpInterface
{
    public function getData(string $endpoint, array $data = []): mixed;

    public function getDataAsync(string $endpoint, array $data = []): PromiseInterface;
}
