<?php

namespace Dcp\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;
use Dcp\Enums\SiteType;
use Dcp\Traits\DataFilterable;
use Illuminate\Support\Collection;

class SiteData extends Data
{
    use DataFilterable;
    
    public function __construct(
        #[MapInputName('Id')]
        public int $siteId,

        #[MapInputName('Name')]
        public string $name,

        #[MapInputName('TimeZone')]
        public string $timeZone,

        #[MapInputName('IsEnabled')]
        public bool $isEnabled,

        #[MapInputName('ShortName')]
        public string $shortName,

        #[MapInputName('SiteType')]
        public SiteType $siteType
    ) {}

    /**
     * Filter sites by type
     * 
     * @param SiteData[] $sites
     */
    public static function filterByType(array $sites, SiteType $siteType): Collection
    {
        return static::filterByProperty($sites, 'siteType', $siteType);
    }
}
