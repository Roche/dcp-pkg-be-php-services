<?php

namespace Dcp\Exceptions;

use Exception;

class DataClassNotFoundException extends Exception
{
    protected $className;

    public function __construct($className)
    {
        parent::__construct("Data class '{$className}' was not found.");
        $this->className = $className;
    }

    public function getClassName()
    {
        return $this->className;
    }
}
