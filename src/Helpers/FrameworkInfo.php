<?php

namespace Dcp\Helpers;

use Illuminate\Support\Collection;
use Dcp\Factories\DcpClientFactory;
use Dcp\Data\SiteData;

class FrameworkInfo
{
    public static function getAllSiteUsers(int $siteId): Collection
    {
        $dao = DcpClientFactory::create();
        return $dao->getData('core.UserQuery', ['SiteId' => $siteId]);
    }

    public static function getAllowedSiteUsers(int $siteId): Collection
    {
        $dao = DcpClientFactory::create();
        return $dao->getData('core.UserQuery', [
            'SiteId' => $siteId,
            'Module' => strtoupper(config('dcp.module_slug'))
        ]);
    }

    public static function getSite(int $siteId): ?SiteData
    {
        $dao = DcpClientFactory::create();
        return $dao->getData('core.SiteQuery', ['SiteId' => $siteId])->first();
    }
}
