<?php

namespace Dcp\Database;

use DateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Dcp\Traits\PreparesIdClause;

class MsSqlHelper
{
    use PreparesIdClause;

    public static function addSystemTimePeriod(string $table, string $startCol = 'SysStartTime', string $endCol = 'SysEndTime'): void
    {
        DB::statement("ALTER TABLE $table ADD $startCol DATETIME2");
        DB::statement("ALTER TABLE $table ADD $endCol DATETIME2");
        DB::statement("UPDATE $table SET $startCol = '19000101 00:00:00.0000000', $endCol = '99991231 23:59:59.9999999'");
        DB::statement("ALTER TABLE $table ALTER COLUMN $startCol DATETIME2 NOT NULL");
        DB::statement("ALTER TABLE $table ALTER COLUMN $endCol DATETIME2 NOT NULL");
        DB::statement("ALTER TABLE $table ADD PERIOD FOR SYSTEM_TIME ($startCol, $endCol)");
    }

    public static function addSystemVersioning(string $table): void
    {
        DB::statement("ALTER TABLE $table SET(SYSTEM_VERSIONING = ON (HISTORY_TABLE = dbo.{$table}_history, DATA_CONSISTENCY_CHECK = ON))");
    }

    public static function dropSystemTimePeriod(string $table, string $startCol = 'SysStartTime', string $endCol = 'SysEndTime'): void
    {
        DB::statement("DROP TABLE {$table}_history");
        DB::statement("ALTER TABLE $table DROP PERIOD FOR SYSTEM_TIME");
        DB::statement("ALTER TABLE $table DROP COLUMN $startCol");
        DB::statement("ALTER TABLE $table DROP COLUMN $endCol");
    }

    public static function dropSystemVersioning(string $table): void
    {
        DB::statement("ALTER TABLE $table SET ( SYSTEM_VERSIONING = Off )");
    }

    public static function auditTrailQuery(string $table, array $primaryKeys, DateTime $startTime, DateTime $endTime, string $startCol = 'SysStartTime', string $endCol = 'SysEndTime'): array
    {
        return DB::select(
            '
            SELECT
                  ' . (count($primaryKeys) > 1 ? 'CONCAT(' . self::prepareIdClause('cr.id', $primaryKeys, ", ', ' ,") . ') AS id,' : null) . '
            CASE
                WHEN ' . self::prepareIdClause('m.id IS NULL', $primaryKeys)
                . ' AND cr.' . $startCol . ' = rs.last_record_time AND cr.' . $startCol . ' = rs.date_created THEN 3
                WHEN ' . self::prepareIdClause('m.id IS NULL', $primaryKeys)
                . " AND cr." . $startCol . " = rs.last_record_time THEN 4
                WHEN cr." . $startCol . " = rs.date_created THEN 1
                ELSE 2
            END AS action_type,
            cr.*
            FROM {$table}
            FOR SYSTEM_TIME CONTAINED IN (:startTime, :endTime) cr
            LEFT JOIN {$table} m
            ON " . self::prepareIdClause('cr.id = m.id', $primaryKeys) . '
            LEFT JOIN(
                SELECT ' . self::prepareIdClause('id', $primaryKeys, ',') . ",
                MIN(" . $startCol . ") AS date_created,
                MAX(" . $startCol . ") AS last_record_time
                FROM {$table} FOR SYSTEM_TIME ALL
                GROUP BY " . self::prepareIdClause('id', $primaryKeys, ',') . '
            ) rs
            ON ' . self::prepareIdClause('cr.id = rs.id', $primaryKeys) . '
            ORDER BY ' . self::prepareIdClause('cr.id', $primaryKeys, ',') . ', cr.' . $startCol,
            [
                'startTime' => Carbon::parse($startTime)->toIso8601String(),
                'endTime'   => Carbon::parse($endTime)->toIso8601String(),
            ]
        );
    }

    public static function forSystemTimeAsOfRaw(string $table, string | DateTime | Carbon $timestamp): string
    {
        $iso = Carbon::parse($timestamp)->toIso8601String();
        return "$table FOR SYSTEM_TIME AS OF '$iso'";
    }

    public static function forSystemTimeFromToRaw(string $table, string | DateTime | Carbon $from, string | DateTime | Carbon $to): string
    {
        $fromIso = Carbon::parse($from)->toIso8601String();
        $toIso = Carbon::parse($to)->toIso8601String();
        return "$table FOR SYSTEM_TIME FROM '$fromIso' TO '$toIso'";
    }

    public static function forSystemTimeBetweenRaw(string $table, string|DateTime|Carbon $from, string|DateTime|Carbon $to): string
    {
        $fromIso = Carbon::parse($from)->toIso8601String();
        $toIso = Carbon::parse($to)->toIso8601String();
        return "$table FOR SYSTEM_TIME BETWEEN '$fromIso' AND '$toIso'";
    }

    public static function forSystemTimeBetweenSymmetricRaw(string $table, string|DateTime|Carbon $from, string|DateTime|Carbon $to): string
    {
        // SQL Server does not support BETWEEN SYMMETRIC, so just alias to BETWEEN
        return self::forSystemTimeBetweenRaw($table, $from, $to);
    }
}
