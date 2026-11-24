<?php

declare(strict_types=1);

namespace Dcp\Helpers;

use JsonException;

class Json
{
    /**
     * @throws JsonException
     */
    public static function decode(string $json, ?bool $associative = null, int $flags = 0, int $depth = 512): mixed
    {
        return json_decode($json, $associative, $depth, JSON_THROW_ON_ERROR | $flags);
    }

    /**
     * @throws JsonException
     */
    public static function decodeAssoc(string $json, ?bool $associative = true, int $flags = 0, int $depth = 512): mixed
    {
        return json_decode($json, $associative, $depth, JSON_THROW_ON_ERROR | $flags);
    }

    /**
     * @throws JsonException
     */
    public static function encode(mixed $value, int $flags = 0, int $depth = 512): string|false
    {
        return json_encode($value, JSON_THROW_ON_ERROR | $flags, $depth);
    }

    public static function isJson($string): bool
    {
        if (!is_string($string)) {
            return false;
        }

        /** @noinspection JsonEncodingApiUsageInspection */
        $data = json_decode($string, true);

        if (!is_array($data)) {
            return false;
        }

        return json_last_error() === JSON_ERROR_NONE;
    }
}
