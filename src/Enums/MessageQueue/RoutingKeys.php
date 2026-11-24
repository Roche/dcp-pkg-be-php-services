<?php

namespace Dcp\Enums\MessageQueue;

use Dcp\Traits\EnumToArray;

enum RoutingKeys: string
{
    use EnumToArray;

    case DEVICE_DELETE = 'DeviceDeleteRoutingKey';
    case SITE_DELETE = 'SiteDeleteRoutingKey';
}
