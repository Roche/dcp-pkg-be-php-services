<?php

namespace Dcp\Helpers;

use InvalidArgumentException;

class ExternalWebIdHelper
{
    public const PREFIX = 'D1C';
    public const SEPARATOR = 'P';

    public static function toInternalWebId(string $externalWebId, int $datasourceId): string
    {
        if ($datasourceId > pow(36, 5)) {
            throw new InvalidArgumentException("The datasource is out of range and can't be encoded");
        }

        return self::PREFIX . self::toBase36String($datasourceId) . self::SEPARATOR . $externalWebId;
    }

    public static function decode(string $internalWebId): array
    {
        if (!self::isInternalWebId($internalWebId)) {
            return [null, $internalWebId];
        }

        $pos = strpos($internalWebId, self::SEPARATOR);
        $base36 = substr($internalWebId, strlen(self::PREFIX), 5);
        $externalWebId = substr($internalWebId, $pos + 1);

        if ($base36 === '' || $externalWebId === '') {
            throw new InvalidArgumentException('Invalid internal WebId format');
        }

        $datasourceId = (int) base_convert($base36, 36, 10);

        return [$datasourceId, $externalWebId];
    }

    public static function toExternalWebId(string $internalWebId): string
    {
        try {
            [, $externalWebId] = self::decode($internalWebId);
            return $externalWebId;
        } catch (\Throwable) {
            return $internalWebId;
        }
    }

    protected static function toBase36String(int $value, int $length = 5): string
    {
        $base36 = strtoupper(base_convert((string) $value, 10, 36));
        return str_pad($base36, $length, '0', STR_PAD_LEFT);
    }

    public static function isInternalWebId(string $value): bool
    {
        return preg_match(
            '/^' . self::PREFIX . '[A-Z0-9]{5}' . self::SEPARATOR . '.+$/',
            $value
        ) === 1;
    }
}
