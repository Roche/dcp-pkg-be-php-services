<?php

namespace Dcp\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;
use Dcp\Traits\DataFilterable;

class UserData extends Data
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
        public string $displayName
    ) {}
}
