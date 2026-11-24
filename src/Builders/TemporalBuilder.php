<?php

namespace Dcp\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

use Dcp\Database\MsSqlHelper;
use Dcp\Database\PgSqlHelper;

class TemporalBuilder extends Builder
{
    /**
     * Applies a temporal query using the appropriate helper for the current database driver.
     *
     * @param string $helperMethod The helper method to call (e.g. forSystemTimeAsOfRaw).
     * @param mixed ...$args Arguments to pass to the helper method (usually timestamps).
     * @return $this
     */
    protected function applyTemporalQuery(string $helperMethod, ...$args): self
    {
        $table = $this->getModel()->getTable();
        $driver = $this->getConnection()->getDriverName();

        $helper = $driver === 'sqlsrv' ? MsSqlHelper::class : PgSqlHelper::class;

        // Prepend table name to arguments
        array_unshift($args, $table);

        // Call the helper method dynamically
        $rawTable = call_user_func_array([$helper, $helperMethod], $args);

        $this->from(DB::raw($rawTable));
        return $this;
    }

    /**
     * Query for records as of a specific point in time.
     *
     * @param string|\DateTime|\Carbon\Carbon $timestamp
     * @return $this
     */
    public function forSystemTimeAsOf(string $timestamp): self
    {
        return $this->applyTemporalQuery('forSystemTimeAsOfRaw', $timestamp);
    }

    /**
     * Query for records from one time to another.
     *
     * @param string|\DateTime|\Carbon\Carbon $from
     * @param string|\DateTime|\Carbon\Carbon $to
     * @return $this
     */
    public function forSystemTimeFromTo(string $from, string $to): self
    {
        return $this->applyTemporalQuery('forSystemTimeFromToRaw', $from, $to);
    }

    /**
     * Query for records between two points in time.
     *
     * @param string|\DateTime|\Carbon\Carbon $from
     * @param string|\DateTime|\Carbon\Carbon $to
     * @return $this
     */
    public function forSystemTimeBetween(string $from, string $to): self
    {
        return $this->applyTemporalQuery('forSystemTimeBetweenRaw', $from, $to);
    }

    /**
     * Query for records between two points in time using symmetric bounds.
     *
     * @param string|\DateTime|\Carbon\Carbon $from
     * @param string|\DateTime|\Carbon\Carbon $to
     * @return $this
     */
    public function forSystemTimeBetweenSymmetric(string $from, string $to): self
    {
        return $this->applyTemporalQuery('forSystemTimeBetweenSymmetricRaw', $from, $to);
    }
}
