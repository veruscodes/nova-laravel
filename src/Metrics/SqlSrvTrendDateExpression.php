<?php

namespace Laravel\Nova\Metrics;

class SqlSrvTrendDateExpression extends TrendDateExpression
{
    /**
     * Get the value of the expression.
     */
    public function getValue(): string
    {
        $column = $this->wrap($this->column);
        $offset = $this->offset();

        if ($offset >= 0) {
            $interval = $offset;
        } else {
            $interval = '-'.($offset * -1);
        }

        $date = "DATEADD(hour, {$interval}, {$column})";

        return match ($this->unit) {
            'month' => "FORMAT({$date}, 'yyyy-MM')",
            'week' => "concat(YEAR({$date}), '-', datepart(ISO_WEEK, {$date}))",
            'day' => "FORMAT({$date}, 'yyyy-MM-dd')",
            'hour' => "FORMAT({$date}, 'yyyy-MM-dd HH:00')",
            // minute
            default => "FORMAT({$date}, 'yyyy-MM-dd HH:mm:00')",
        };
    }
}
