<?php

namespace Dcp\Traits;

use Dcp\Exceptions\DataClassNotFoundException;

trait FormatsDcpClientResponse
{
    public static function applyDataClassOnResponse(array $responseData, string $dataType): mixed
    {
        $className = rtrim($dataType, '[]');

        if (!str_contains($dataType, "\\")) {
            $className = "Dcp\\Data\\" . $className;
        } else {
            loadClassManually($className);
        }

        if (str_ends_with($dataType, '[]') && class_exists($className)) {
            return collect(array_map(fn($item) => $className::from($item), $responseData));
        }

        if (class_exists($className)) {
            return $className::from($responseData);
        }

        // Handle scalar types and fallback
        return match ($dataType) {
            'string' => (string) $responseData,
            'int' => (int) $responseData,
            'bool' => (bool) $responseData,
            default => throw new DataClassNotFoundException("Data class (" . $className . ") not found."),
        };
    }
}
