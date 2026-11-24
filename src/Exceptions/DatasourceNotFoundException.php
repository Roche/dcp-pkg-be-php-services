<?php

namespace Dcp\Exceptions;

use Exception;

class DatasourceNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct("No matching Datasource found for this module.");
    }
}
