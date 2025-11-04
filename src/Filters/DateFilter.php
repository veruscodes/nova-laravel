<?php

namespace Laravel\Nova\Filters;

abstract class DateFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'date-filter';

    /**
     * Set the first day of the week.
     *
     * @return $this
     *
     * @deprecated 5.6.0 Feature no longer supported.
     */
    #[\Deprecated('Feature no longer available', since: '5.6.0')]
    public function firstDayOfWeek(int $day)
    {
        return $this->withMeta([__FUNCTION__ => $day]);
    }
}
