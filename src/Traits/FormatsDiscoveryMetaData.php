<?php

namespace Dcp\Traits;

use Dcp\Helpers\Json;

trait FormatsDiscoveryMetaData
{
    /**
     * Retrieves formated service description.
     */
    private function getFormatedService(): array
    {
        return [
            'ID' => $this->serviceConfig['id'],
            'Name' => $this->serviceConfig['id'],
            'Address' => preg_replace(
                '/^https?:\/\//',
                '',
                $this->serviceConfig['address']
            ),
            'Port' => intval($this->serviceConfig['port']),
            'Tags' => explode(',', $this->serviceConfig['tags']),
            'Check' => [
                'http' => $this->serviceConfig['address'] . ':' . intval($this->serviceConfig['port']) . '/api/health', // The URL to check
                'interval' => '240s', // How often to check
                'timeout' => '5s', // Timeout for the check
            ],
            'Meta' => $this->getMetaData()
        ];
    }

    /**
     * Retrieves defined MetaData.
     */
    private function getMetaData(): array
    {
        $metaDataTemplate = [
            'ModuleName' => $this->serviceConfig['name'],
            'DataTypes' => [],
            'Resources' => []
        ];

        $metaData = [
            ...$metaDataTemplate,
            ...($this->serviceConfig['meta_data'] ?? [])
        ];

        $metaData = array_map(function ($metaRecord) {
            if (!is_string($metaRecord)) {
                $metaRecord = JSON::encode($metaRecord);
            }
            return $metaRecord;
        }, $metaData);

        return $metaData;
    }
}
