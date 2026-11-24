<?php

namespace Dcp\Exceptions;

use Exception;

class InvalidClientTypeException extends Exception
{
    protected $clientType;

    public function __construct($clientType)
    {
        parent::__construct("'{$clientType}' is an invalid client type.");
        $this->clientType = $clientType;
    }

    public function getClientType()
    {
        return $this->clientType;
    }
}
