<?php

namespace Laravel\Nova\Metrics;

use Carbon\CarbonImmutable;
use DateTime;
use DateTimeZone;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Stringable;

abstract class TrendDateExpression implements Stringable
{
    /**
     * Create a new raw query expression.
     */
    public function __construct(
        public Builder $query,
        public string $column,
        public string $unit,
        public string $timezone
    ) {
        //
    }

    /**
     * Get the timezone offset for the user's timezone.
     *
     * @return int
     */
    public function offset()
    {
        $timezoneOffset = static function ($timezone) {
            return (new DateTime(CarbonImmutable::now()->format('Y-m-d H:i:s'), new DateTimeZone($timezone)))->getOffset() / 60 / 60;
        };

        if ($this->timezone) {
            $appOffset = $timezoneOffset(config('app.timezone'));
            $userOffset = $timezoneOffset($this->timezone);

            return $userOffset - $appOffset;
        }

        return 0;
    }

    /**
     * Wrap the given value using the query's grammar.
     */
    protected function wrap(string $value): string
    {
        return $this->query->getQuery()->getGrammar()->wrap($value);
    }

    /**
     * Get the value of the expression.
     */
    abstract public function getValue(): string;

    /**
     * Get the value of the expression.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getValue();
    }
}
