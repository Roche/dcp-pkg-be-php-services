<?php

namespace Dcp\Database;

use DateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Dcp\Traits\PreparesIdClause;

class PgSqlHelper
{
    use PreparesIdClause;

    public static function listTableNames(): array
    {
        $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        return array_map(fn($row) => $row->tablename, $tables);
    }

    public static function listTableColumns(string $table): array
    {
        $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = ?", [$table]);
        return array_map(fn($row) => $row->column_name, $columns);
    }

    public static function addSystemTimePeriod(string $table, string $startCol = 'SysStartTime', string $endCol = 'SysEndTime'): void
    {
        DB::statement("SELECT periods.add_system_time_period('$table', '$startCol', '$endCol')");
    }

    public static function addSystemVersioning(string $table): void
    {
        DB::statement("SELECT periods.add_system_versioning('$table')");
    }

    public static function dropSystemTimePeriod(string $table, string $startCol = 'SysStartTime', string $endCol = 'SysEndTime'): void
    {
        DB::statement("SELECT periods.drop_system_time_period('$table')");
        DB::statement("DROP TABLE {$table}_history");
        DB::statement("ALTER TABLE $table DROP COLUMN \"$startCol\"");
        DB::statement("ALTER TABLE $table DROP COLUMN \"$endCol\"");
    }

    public static function dropSystemVersioning(string $table): void
    {
        DB::statement("SELECT periods.drop_system_versioning('$table')");
    }

    public static function auditTrailQuery(string $table, array $primaryKeys, DateTime $startTime, DateTime $endTime, string $startCol = 'SysStartTime', string $endCol = 'SysEndTime'): array
    {
        $query = '
            SELECT
                  ' . (count($primaryKeys) > 1 ? 'CONCAT(' . self::prepareIdClause('cr."id"', $primaryKeys, ", ', ' ,") . ') AS id,' : null) . '
            CASE
                WHEN ' . self::prepareIdClause('m.id IS NULL', $primaryKeys)
            . ' AND cr."' . $startCol . '" = rs.last_record_time AND cr."' . $startCol . '" = rs.date_created THEN 3
                WHEN ' . self::prepareIdClause('m.id IS NULL', $primaryKeys)
            . " AND cr.\"$startCol\" = rs.last_record_time THEN 4
                WHEN cr.\"$startCol\" = rs.date_created THEN 1
                ELSE 2
            END AS action_type,
            cr.*
            FROM {$table}__from_to(:startTime, :endTime) cr
            LEFT JOIN {$table} m
            ON " . self::prepareIdClause('cr.id = m.id', $primaryKeys) . '
            LEFT JOIN(
                SELECT ' . self::prepareIdClause('id', $primaryKeys, ',') . ",
                MIN(\"" . $startCol . "\") AS date_created,
                MAX(\"" . $startCol . "\") AS last_record_time
                FROM {$table}__from_to('-infinity', 'infinity')
                GROUP BY " . self::prepareIdClause('id', $primaryKeys, ',') . '
            ) rs
            ON ' . self::prepareIdClause('cr.id = rs.id', $primaryKeys) . '
            WHERE cr."'. $startCol . '" BETWEEN  :startTime AND :endTime OR cr."'. $endCol . '" BETWEEN  :startTime AND :endTime
            ORDER BY ' . self::prepareIdClause('cr.id', $primaryKeys, ',') . ', cr."' . $startCol . '"';

        return DB::select(
            $query,
            [
                'startTime' => Carbon::parse($startTime)->toIso8601String(),
                'endTime'   => Carbon::parse($endTime)->toIso8601String(),
            ]
        );
    }

    public static function forSystemTimeAsOfRaw(string $table, string|DateTime|Carbon $timestamp): string
    {
        $iso = Carbon::parse($timestamp)->toIso8601String();
        return "{$table}__as_of('{$iso}')";
    }

    public static function forSystemTimeFromToRaw(string $table, string|DateTime|Carbon $from, string|DateTime|Carbon $to): string
    {
        $fromIso = Carbon::parse($from)->toIso8601String();
        $toIso = Carbon::parse($to)->toIso8601String();
        return "{$table}__from_to('{$fromIso}', '{$toIso}')";
    }

    public static function forSystemTimeBetweenRaw(string $table, string|DateTime|Carbon $from, string|DateTime|Carbon $to): string
    {
        $fromIso = Carbon::parse($from)->toIso8601String();
        $toIso = Carbon::parse($to)->toIso8601String();
        return "{$table}__between('{$fromIso}', '{$toIso}')";
    }

    public static function forSystemTimeBetweenSymmetricRaw(string $table, string|DateTime|Carbon $from, string|DateTime|Carbon $to): string
    {
        $fromIso = Carbon::parse($from)->toIso8601String();
        $toIso = Carbon::parse($to)->toIso8601String();
        return "{$table}__between_symmetric('{$fromIso}', '{$toIso}')";
    }
}
