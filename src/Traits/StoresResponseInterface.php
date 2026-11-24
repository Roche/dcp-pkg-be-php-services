<?php

namespace Dcp\Traits;

use Psr\Http\Message\ResponseInterface;
use Illuminate\Support\Collection;

trait StoresResponseInterface
{
    protected ResponseInterface $responseData;

    public function getResponse(): ResponseInterface
    {
        return $this->responseData;
    }

    public function getResponseContents(): string
    {
        return $this->responseData->getBody()->getContents();
    }

    public function getResponseJson(): mixed
    {
        return json_decode(
            $this->getResponseContents(),
            true
        );
    }

    public function getResponseCollection(): Collection
    {
        return collect($this->getResponseJson());
    }
}
