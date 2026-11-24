<?php

namespace Dcp\Data\Items;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;

class UserInfoSiteData extends Data
{
    public function __construct(
        #[MapInputName('Id')]
        public int $siteId,

        #[MapInputName('Name')]
        public string $name,

        #[MapInputName('TimeZone')]
        public string $timeZone,

        #[MapInputName('IsEnabled')]
        public bool $isEnabled
    ) {}
}
