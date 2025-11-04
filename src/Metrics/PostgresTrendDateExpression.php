<?php

namespace Laravel\Nova\Metrics;

class PostgresTrendDateExpression extends TrendDateExpression
{
    /**
     * Get the value of the expression.
     */
    public function getValue(): string
    {
        $offset = $this->offset();

        if ($offset > 0) {
            $interval = '+ interval \''.$offset.' hour\'';
        } elseif ($offset === 0) {
            $interval = '';
        } else {
            $interval = '- interval \''.($offset * -1).' HOUR\'';
        }

        return match ($this->unit) {
            'month' => "to_char({$this->wrap($this->column)} {$interval}, 'YYYY-MM')",
            'week' => "to_char({$this->wrap($this->column)} {$interval}, 'IYYY-IW')",
            'day' => "to_char({$this->wrap($this->column)} {$interval}, 'YYYY-MM-DD')",
            'hour' => "to_char({$this->wrap($this->column)} {$interval}, 'YYYY-MM-DD HH24:00')",
            // 'minute'
            default => "to_char({$this->wrap($this->column)} {$interval}, 'YYYY-MM-DD HH24:mi:00')",
        };
    }
}
