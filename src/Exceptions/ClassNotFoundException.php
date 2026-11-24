<?php

namespace Dcp\Exceptions;

use Exception;

class ClassNotFoundException extends Exception
{
    protected $className;
    protected $filePath;

    public function __construct($className, $filePath)
    {
        parent::__construct("Class file for {$className} not found at {$filePath}.");
        $this->className = $className;
        $this->filePath = $filePath;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }
}
