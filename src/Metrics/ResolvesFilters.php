<?php

namespace Laravel\Nova\Metrics;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Laravel\Nova\Filters\FilterDecoder;
use Laravel\Nova\Http\Requests\NovaRequest;

trait ResolvesFilters
{
    /**
     * Filters for the metric.
     */
    protected ?Collection $filters = null;

    /**
     * Set filters for current metric.
     *
     * @return $this
     */
    public function setAvailableFilters(Collection $filters)
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Apply filter query.
     */
    public function applyFilterQuery(NovaRequest $request, Builder $query): Builder
    {
        if ($this->filters instanceof Collection) {
            (new FilterDecoder($request->filter, $this->filters))
                ->filters()
                ->each->__invoke($request, $query);
        }

        return $query;
    }
}
