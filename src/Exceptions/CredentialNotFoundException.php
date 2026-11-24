<?php

namespace Dcp\Exceptions;

use Exception;

class CredentialNotFoundException extends Exception
{
    protected $key;

    public function __construct($key)
    {
        parent::__construct("The key '{$key}' was not found in the credential store.");
        $this->key = $key;
    }

    public function getKey()
    {
        return $this->key;
    }
}
