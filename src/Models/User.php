<?php

/** @noinspection PropertyInitializationFlawsInspection */

namespace Dcp\Models;

use Dcp\Factories\DcpClientFactory;
use Dcp\Data\UserInfoData;
use Dcp\Data\Items\UserInfoSettingsData;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use JsonException;

/** @OA\Schema(
 *     schema="User",
 *     @OA\Property(
 *         property="UserID",
 *         type="integer"
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string"
 *     ),
 *     @OA\Property(
 *         property="UnixId",
 *         type="string"
 *     ),
 *     @OA\Property(
 *         property="DisplayName",
 *         type="string"
 *     ),
 *     @OA\Property(
 *         property="TimeFormat",
 *         type="string"
 *     ),
 *     @OA\Property(
 *         property="DateFormat",
 *         type="string"
 *     )
 * ),
 * @property string $username
 * @property int $id
 * @property array $scopes
 * @property array|object|null $details
 * @property string $displayName
 * @property Collection $data
 */
class User implements AuthenticatableContract
{
    use Notifiable;

    protected array $attributes = [];

    protected array $casts = [
        'id' => 'int',
    ];

    protected array|object|null $userDetails = null;

    public function __isset($key): bool
    {
        return Arr::has($this->attributes, $key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __get(string $key): mixed
    {
        if (!Arr::has($this->attributes, $key) || $this->attributes[$key] === null) {
            return null;
        }

        $value = $this->attributes[$key];
        $cast = $this->casts[$key] ?? null;

        return match ($cast) {
            'int' => (int)$value,
            'float' => (float)$value,
            'string' => (string)$value,
            default => $value,
        };
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): int|string|null
    {
        return $this->id;
    }

    public function getAuthPassword(): ?string
    {
        return null;
    }

    public function getAuthPasswordName(): ?string
    {
        return null;
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        // This method is intentionally left blank because this model does not support "remember me" functionality.
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }

    public function setDetailsAttribute(UserInfoData $details): void
    {
        $this->userDetails = $details;
    }

    public function getEmail(): ?string
    {
        return $this->getUserDetails()->email ?? null;
    }

    public function getUnixId(): ?string
    {
        return $this->getUserDetails()->unixId ?? null;
    }

    public function getDisplayName(): ?string
    {
        return $this->getUserDetails()->displayName ?? null;
    }

    public function getUserSettings(): ?UserInfoSettingsData
    {
        return $this->getUserDetails()->settings ?? null;
    }

    public function getDateFormat(): ?string
    {
        return $this->getUserDetails()->settings->dateFormat ?? null;
    }

    public function getTimeFormat(): ?string
    {
        return $this->getUserDetails()->settings->timeFormat ?? null;
    }

    public function isAdmin(int $siteId): bool
    {
        return $this->isSiteAdmin($siteId);
    }

    public function isSiteAdmin(int $siteId): bool
    {
        foreach ($this->scopes as $scope) {
            if (
                (int)$scope->SiteId > 0 &&
                (int)$scope->SiteId === (int)$siteId &&
                $scope->Module === 'agnostic' &&
                $scope->Permission === 'ADMIN'
            ) {
                return true;
            }
        }

        return false;
    }

    public function isPlatformAdmin(): bool
    {
        foreach ($this->scopes as $scope) {
            if (
                (int)$scope->SiteId === 0 &&
                $scope->SiteName === 'GLOBAL' &&
                $scope->Module === 'agnostic' &&
                $scope->Permission === 'PLATFORMADMIN'
            ) {
                return true;
            }
        }

        return false;
    }

    public function isServerClient(): bool
    {
        return $this->persistedGrantsType === 3;
    }

    public function isActualUser(): bool
    {
        return $this->persistedGrantsType === 1;
    }

    /**
     * @throws JsonException
     */
    protected function getUserDetails(): object
    {
        // call via UserInfo
        if (!$this->userDetails) {
            $coreConnection = DcpClientFactory::create();
            $coreData = $coreConnection->getData('core.UserInfo');

            if (empty($coreData)) {
                abort(500, 'User not found.');
            }

            $this->userDetails = $coreData ?? (object)[];
        }

        return $this->userDetails;
    }
}
