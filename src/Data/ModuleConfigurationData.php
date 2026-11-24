<?php

namespace Dcp\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;
use Dcp\Traits\DataFilterable;
use Illuminate\Support\Collection;

class ModuleConfigurationData extends Data
{
    use DataFilterable;

    public function __construct(
        #[MapInputName('Id')]
        public int $moduleId,

        #[MapInputName('Name')]
        public string $moduleName,

        #[MapInputName('SiteId')]
        public int $siteId,

        #[MapInputName('CalculationNodeType')]
        public string $calculationNodeType,

        #[MapInputName('IsEnabled')]
        public bool $isEnabled,

        #[MapInputName('IsValidated')]
        public bool $isValidated,

        #[MapInputName('ConnectionString')]
        public array $connectionString
    ) {}

    /**
     * Filter modules by calculation node type
     * 
     * @param ModuleConfigurationData[] $modules
     */
    public static function filterByNodeType(array $modules, string $nodeType): Collection
    {
        return static::filterByProperty($modules, 'calculationNodeType', $nodeType);
    }
}
