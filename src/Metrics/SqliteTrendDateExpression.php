<?php

namespace Laravel\Nova\Metrics;

class SqliteTrendDateExpression extends TrendDateExpression
{
    /**
     * Get the value of the expression.
     */
    public function getValue(): string
    {
        $offset = $this->offset();

        if ($offset > 0) {
            $interval = '\'+'.$offset.' hour\'';
        } elseif ($offset === 0) {
            $interval = '\'+0 hour\'';
        } else {
            $interval = '\'-'.($offset * -1).' hour\'';
        }

        return match ($this->unit) {
            'month' => "strftime('%Y-%m', datetime({$this->wrap($this->column)}, {$interval}))",
            'week' => "strftime('%Y-', datetime({$this->wrap($this->column)}, {$interval})) ||
                        (
                            strftime('%W', datetime({$this->wrap($this->column)}, {$interval})) +
                            (1 - strftime('%W', strftime('%Y', datetime({$this->wrap($this->column)}, {$interval})) || '-01-04'))
                        )",
            'day' => "strftime('%Y-%m-%d', datetime({$this->wrap($this->column)}, {$interval}))",
            'hour' => "strftime('%Y-%m-%d %H:00', datetime({$this->wrap($this->column)}, {$interval}))",
            // minute
            default => "strftime('%Y-%m-%d %H:%M:00', datetime({$this->wrap($this->column)}, {$interval}))",
        };
    }
}
