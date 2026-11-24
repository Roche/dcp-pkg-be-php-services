<?php

namespace Dcp\Traits;

use Illuminate\Support\Str;
use ValueError;

trait EnumToArray
{
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function array(): array
    {
        return array_combine(self::values(), self::names());
    }

    public static function contains(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    public static function has(string $value): bool
    {
        foreach (self::cases() as $case) {
            if ($value === $case->value) {
                return true;
            }
        }

        return false;
    }

    public static function getByName(string $name): self
    {
        $name = strtoupper($name);

        foreach (self::cases() as $case) {
            if ($name === $case->name) {
                return $case;
            }
        }
        throw new ValueError(sprintf('Invalid name %s', $name));
    }

    public static function get($value): self
    {
        foreach (self::cases() as $case) {
            if ($value === $case->value) {
                return $case;
            }
        }
        throw new ValueError(sprintf('Invalid value %s', $value));
    }

    public static function getOptions(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }

    public function getLabel(): string
    {
        return is_string($this->value) ? Str::title(Str::replace(['_', '-'], ' ', $this->value)) : $this->value;
    }

    public static function getConfig(): array
    {
        $result = [];

        foreach (self::cases() as $case) {
            $result[] = [
                'text'  => Str::title($case->name),
                'value' => $case->value,
            ];
        }

        return $result;
    }
}
