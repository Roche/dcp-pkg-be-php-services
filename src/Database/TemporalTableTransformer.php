<?php

namespace Dcp\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use Dcp\Database\MsSqlHelper;
use Dcp\Database\PgSqlHelper;

class TemporalTableTransformer
{
    public static function migrate(string $table): void
    {
        $dbType = config('database.default');

        if (!Schema::hasColumn($table, 'last_modified_by')) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedInteger('last_modified_by')->nullable();
            });
        }

        if ($dbType == 'sqlsrv') {
            MsSqlHelper::addSystemTimePeriod($table);
            MsSqlHelper::addSystemVersioning($table);
        } elseif ($dbType === 'pgsql') {
            PgSqlHelper::addSystemTimePeriod($table);
            PgSqlHelper::addSystemVersioning($table);
        }
    }

    public static function rollback(string $table): void
    {
        $dbType = config('database.default');

        if ($dbType == 'sqlsrv') {
            MsSqlHelper::dropSystemVersioning($table);
            MsSqlHelper::dropSystemTimePeriod($table);

            if (Schema::hasColumn($table, 'last_modified_by')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('last_modified_by');
                });
            }
        } elseif ($dbType === 'pgsql') {
            PgSqlHelper::dropSystemVersioning($table);
            PgSqlHelper::dropSystemTimePeriod($table);

            if (Schema::hasColumn($table, 'last_modified_by')) {
                DB::statement("ALTER TABLE $table DROP COLUMN last_modified_by CASCADE");
            }
        }
    }
}
