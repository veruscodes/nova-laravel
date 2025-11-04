<?php

namespace Laravel\Nova\Metrics;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;

class TrendDateExpressionFactory
{
    use Macroable;

    /**
     * Create a new trend expression instance.
     *
     * @return \Laravel\Nova\Metrics\TrendDateExpression
     *
     * @throws \InvalidArgumentException
     */
    public static function make(Builder $query, string $column, string $unit, string $timezone)
    {
        $driver = $query->getConnection()->getDriverName();

        if (static::hasMacro($driver)) {
            return static::$driver($query, $column, $unit, $timezone);
        }

        return match ($driver) {
            'sqlite' => new SqliteTrendDateExpression($query, $column, $unit, $timezone),
            'mysql', 'mariadb' => new MySqlTrendDateExpression($query, $column, $unit, $timezone),
            'pgsql' => new PostgresTrendDateExpression($query, $column, $unit, $timezone),
            'sqlsrv' => new SqlSrvTrendDateExpression($query, $column, $unit, $timezone),
            default => throw new InvalidArgumentException('Trend metric helpers are not supported for this database.'),
        };
    }
}
