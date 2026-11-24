<?php

namespace Dcp\Validation;

use Illuminate\Support\Arr;

class ValidationStatus
{
    public const API_STATUS = 'api';
    public const DATA_SOURCE_STATUS = 'data_source';
    public const CALCULATION_ENGINE_STATUS = 'calculation_engine';
    public const ENTITY_STATUS = 'entity';
    protected array $statusDetails;

    public function __construct()
    {
        $this->statusDetails = [
            self::API_STATUS                => config('dcp.validation') ? 'true' : 'false',
            self::DATA_SOURCE_STATUS        => 'undefined',
            self::CALCULATION_ENGINE_STATUS => 'undefined',
            self::ENTITY_STATUS             => 'undefined',
        ];
    }

    public function update($field, $value): void
    {
        if ($field === 'api') {
            abort(500, sprintf('"%s" needs to be defined via env.', $field));
        }

        if (!Arr::has($this->statusDetails, $field)) {
            abort(500, sprintf('"%s" is not expected in the validation status.', $field));
        }

        if (!is_bool($value) && !in_array($value, ['true', 'false'])) {
            abort(500, sprintf('"%s" value is expected to be boolean.', $field));
        }

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        //Don't overwrite false values
        if (Arr::get($this->statusDetails, $field) !== 'false') {
            $this->statusDetails[$field] = $value;
        }
    }

    public function get($field): ?string
    {
        return $this->statusDetails[$field] ?? null;
    }

    public function stringify(): string
    {
        return implode(',', array_map('ucfirst', array_values($this->statusDetails)));
    }
}
