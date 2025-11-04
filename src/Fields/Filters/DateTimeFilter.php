<?php

namespace Laravel\Nova\Fields\Filters;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Http\Requests\NovaRequest;

class DateTimeFilter extends DateFilter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'date-time-field';

    /**
     * Apply the filter to the given query.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, Builder $query, mixed $value)
    {
        $value = collect($value)->transform(static function ($value) {
            return ! empty($value) ? rescue(static function () use ($value) {
                return CarbonImmutable::parse($value);
            }, null) : null;
        });

        if ($value->filter()->isNotEmpty()) {
            $this->field->applyFilter($request, $query, $value->all());
        }

        return $query;
    }
}
