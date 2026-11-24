<?php

namespace Dcp\Helpers;

use Dcp\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class CurrentUser
{
    public static function get(): User|Authenticatable|null
    {
        return Auth::user();
    }

    public static function id(): ?int
    {
        $user = self::get();

        if (!$user) {
            return null;
        }

        return (int)$user->id;
    }

    public static function isAdmin(int $currentSiteId): bool
    {
        return self::isSiteAdmin($currentSiteId);
    }

    public static function isSiteAdmin(int $currentSiteId): bool
    {
        if (!$currentSiteId) {
            return false;
        }
        
        $user = self::get();

        if (!$user) {
            return false;
        }

        return $user->isSiteAdmin($currentSiteId);
    }

    public static function isPlatformAdmin(): bool
    {
        $user = self::get();

        if (!$user) {
            return false;
        }

        return $user->isPlatformAdmin();
    }

    public static function getScopes(?int $siteId = null, ?string $moduleSlug = null): array
    {
        $user = self::get();

        if (!$user) {
            return [];
        }

        $scopes = $user->scopes ?? [];

        if (!$scopes) {
            return [];
        }

        if (!$siteId && !$moduleSlug) {
            return $scopes;
        }

        return array_values(array_filter(
            $scopes,
            static function ($scope) use ($siteId, $moduleSlug) {
                if ($siteId !== null && $scope->SiteId !== $siteId) {
                    return false;
                }

                if ($moduleSlug !== null && $scope->Module !== strtoupper($moduleSlug)) {
                    return false;
                }

                return true;
            }
        ));
    }

    public static function getScopeNames(?int $siteId = null, ?string $moduleSlug = null): array
    {
        $scopes = self::getScopes($siteId, $moduleSlug);

        $scopeNames = array_values(array_filter(array_map(
            static function ($scope) {
                return $scope->Permission;
            },
            $scopes,
        )));

        if ($siteId && self::isSiteAdmin($siteId)) {
            $scopeNames[] = 'ADMIN';
        }

        return $scopeNames;
    }
}
