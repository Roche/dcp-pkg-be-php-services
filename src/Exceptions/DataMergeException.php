<?php

namespace Dcp\Exceptions;

use Exception;

class DataMergeException extends Exception
{
    protected $firstClassName;
    protected $secondClassName;

    public function __construct($firstClassName, $secondClassName)
    {
        $shortFirstClassName = $this->getClassBaseName($firstClassName);
        $shortSecondClassName = $this->getClassBaseName($secondClassName);
        
        parent::__construct("Tried to merge {$shortSecondClassName} into {$shortFirstClassName}, which is not allowed.");
        $this->firstClassName = $firstClassName;
        $this->secondClassName = $secondClassName;
    }

    public function getClassNames(): array
    {
        return [
            $this->firstClassName,
            $this->secondClassName
        ];
    }

    
    private function getClassBaseName(string $className): string
    {
        return basename(str_replace('\\', '/', $className));
    }
}
