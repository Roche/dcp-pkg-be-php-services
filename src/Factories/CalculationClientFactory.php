<?php

namespace Dcp\Factories;

use Dcp\Exceptions\InvalidClientTypeException;
use Dcp\Factories\DcpClientFactory;
use Dcp\Contracts\CalculationInterface;
use Dcp\Exceptions\DatasourceNotFoundException;

class CalculationClientFactory
{
    public static function create(int $siteId, ?array $connectionFilter = null): CalculationInterface
    {
        $dcpClient = DcpClientFactory::create();

        $calcNodeSettings = $dcpClient->getData('core.ModuleConfiguration', [
            'siteId' => $siteId,
            'module' => config('dcp.module_slug')
        ]);

        if (!empty($connectionFilter)) {
            foreach ($connectionFilter as $key => $value) {
                $calcNodeSettings = $calcNodeSettings->where($key, $value);
            }
        }

        $calcNodeSetting = $calcNodeSettings->first();
        
        if (is_null($calcNodeSetting)) {
            throw new DatasourceNotFoundException();
        }

        $clientMappings = config('dcp.calculation_clients', []);

        if (!isset($clientMappings[$calcNodeSetting->Type])) {
            throw new InvalidClientTypeException($calcNodeSetting->Type);
        }

        return new $clientMappings[$calcNodeSetting->Type]($calcNodeSetting);
    }
}
