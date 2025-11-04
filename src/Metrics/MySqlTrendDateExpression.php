<?php

namespace Laravel\Nova\Metrics;

class MySqlTrendDateExpression extends TrendDateExpression
{
    /**
     * Get the value of the expression.
     */
    public function getValue(): string
    {
        $offset = $this->offset();

        if ($offset > 0) {
            $interval = '+ INTERVAL '.$offset.' HOUR';
        } elseif ($offset === 0) {
            $interval = '';
        } else {
            $interval = '- INTERVAL '.($offset * -1).' HOUR';
        }

        return match ($this->unit) {
            'month' => "date_format({$this->wrap($this->column)} {$interval}, '%Y-%m')",
            'week' => "date_format({$this->wrap($this->column)} {$interval}, '%x-%v')",
            'day' => "date_format({$this->wrap($this->column)} {$interval}, '%Y-%m-%d')",
            'hour' => "date_format({$this->wrap($this->column)} {$interval}, '%Y-%m-%d %H:00')",
            // 'minute'
            default => "date_format({$this->wrap($this->column)} {$interval}, '%Y-%m-%d %H:%i:00')",
        };
    }
}
