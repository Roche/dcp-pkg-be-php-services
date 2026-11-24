<?php

namespace Dcp\Data\Items;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;

class UserInfoSettingsData extends Data
{
    public function __construct(
        #[MapInputName('TimeFormat')]
        public string $timeFormat,
        
        #[MapInputName('DateFormat')]
        public string $dateFormat,
    ) {}
}