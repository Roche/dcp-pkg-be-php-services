<?php

namespace Dcp\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;
use Dcp\Enums\DataType;
use Dcp\Traits\DataFilterable;
use Illuminate\Support\Collection;

class DataSourceData extends Data
{
    use DataFilterable;

    public function __construct(
        #[MapInputName('Id')]
        public int $datasourceId,

        #[MapInputName('Name')]
        public string $name,

        #[MapInputName('ConnectionType')]
        public string $connectionType,

        #[MapInputName('DataTypes')]
        /** @var array<DataType> */
        public array $dataTypes = [],

        #[MapInputName('ConnectionString')]
        public array $connectionString = [],

        #[MapInputName('SiteId')]
        public ?int $siteId = null,

        #[MapInputName('IsValidated')]
        public bool $isValidated = false
    ) {
        // Cast string array to DataType enum array
        $this->dataTypes = array_map(
            fn($type) => $type instanceof DataType ? $type : DataType::from($type),
            $this->dataTypes
        );
    }

    public function hasDataType(DataType $dataType): bool
    {
        return in_array($dataType, $this->dataTypes, true);
    }

    public static function filterByDataType(array $datasources, DataType $dataType): Collection
    {
        return static::filter(
            $datasources,
            fn(DataSourceData $ds) => $ds->hasDataType($dataType)
        );
    }
}