<?php

namespace Dcp\Helpers;

use ReflectionClass;

use Dcp\Data\ResponseData;
use Dcp\Exceptions\DataMergeException;
use Dcp\Exceptions\DataMergePropsException;

class ResponseDataHelper
{
    public static function digest(ResponseData $first, ResponseData $second, string|callable|null $propertyOrMergeFunction = null): ResponseData
    {
        self::verifyClassTypes($first, $second);

        if (is_callable($propertyOrMergeFunction)) {
            $propertyOrMergeFunction();
            return $first;
        }

        $reflectionClass = new ReflectionClass($first::class);
        $dataProp = collect($reflectionClass->getProperties())
            ->filter(
                fn($prop) => $prop->class == $first::class &&
                    (
                        (is_null($propertyOrMergeFunction) && !in_array($prop->name, ['errors', 'warnings'])) ||
                        (!is_null($propertyOrMergeFunction) && $prop->name == $propertyOrMergeFunction)
                    )
            );

        if ($dataProp->count() !== 1) {
            throw new DataMergePropsException();
        }

        $dataPropName = $dataProp->first()->name;

        $first->warnings = [...$first->warnings, ...$second->warnings];
        $first->errors = [...$first->errors, ...$second->errors];
        $first->{$dataPropName} = [...$first->{$dataPropName}, ...$second->{$dataPropName}];

        return $first;
    }

    public static function inject(
        ResponseData $first,
        ResponseData $second,
        ?string $dataProperty = 'data',
        bool $includeWarnings = true,
        bool $includeErrors = true
    ): ResponseData {
        self::verifyClassTypes($first, $second);

        if ($includeWarnings) {
            $first->warnings = [...$first->warnings, ...$second->warnings];
        }

        if ($includeErrors) {
            $first->errors = [...$first->errors, ...$second->errors];
        }

        if (!is_null($dataProperty)) {
            $dataPath = explode('.', $dataProperty);

            $internalDataRef = &$first;
            foreach ($dataPath as $key) {
                $internalDataRef = &$internalDataRef->{$key};
            }

            $injectDataRef = $second;
            foreach ($dataPath as $key) {
                $injectDataRef = $injectDataRef->{$key};
            }

            /** @disregard 1006 */
            $internalDataRef = [...$internalDataRef, ...$injectDataRef];
        }

        return $first;
    }

    private static function verifyClassTypes(ResponseData $first, ResponseData $second): void
    {
        if ($first::class !== $second::class) {
            throw new DataMergeException($first::class, $second::class);
        }
    }
}
