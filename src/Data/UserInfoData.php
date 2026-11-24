<?php

namespace Dcp\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Dcp\Data\Items\UserInfoSettingsData;
use Dcp\Data\Items\UserInfoSiteData;
use Dcp\Traits\DataFilterable;

class UserInfoData extends Data
{
    use DataFilterable;

    public function __construct(
        #[MapInputName('Id')]
        public int $userId,

        #[MapInputName('Email')]
        public ?string $email,

        #[MapInputName('UnixId')]
        public string $unixId,

        #[MapInputName('DisplayName')]
        public string $displayName,

        #[MapInputName('Settings')]
        public UserInfoSettingsData|array $settings,

        #[MapInputName('Sites')]
        #[DataCollectionOf(UserInfoSiteData::class)]
        public array $sites
    ) {
        if (is_array($this->settings)) {
            $this->settings = UserInfoSettingsData::from($this->settings);
        }
    }
}
