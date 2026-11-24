<?php

namespace Dcp\Traits;

trait PreparesIdClause
{
    protected static function prepareIdClause(string $template, array $primaryKeys, string $glue = 'AND'): string
    {
        $parts = [];

        foreach ($primaryKeys as $primaryKey) {
            $parts[] = str_replace('id', $primaryKey, $template);
        }

        return implode(' ' . $glue . ' ', $parts);
    }
}
