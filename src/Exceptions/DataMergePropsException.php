<?php

namespace Dcp\Exceptions;

use Exception;

class DataMergePropsException extends Exception
{
    public function __construct()
    {
        parent::__construct("Property to merge not found.");
    }
}
