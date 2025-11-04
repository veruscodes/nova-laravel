<?php

namespace Laravel\Nova;

use Illuminate\Support\Collection;
use Laravel\Nova\Http\Requests\NovaRequest;

trait ResolvesFilters
{
    /**
     * Get the filters that are available for the given request.
     *
     * @return \Illuminate\Support\Collection<int, \Laravel\Nova\Filters\Filter>
     */
    public function availableFilters(NovaRequest $request): Collection
    {
        return $this->resolveFilters($request)
                    ->concat($this->resolveFiltersFromFields($request))
                    ->filter->authorizedToSee($request)
                    ->values();
    }

    /**
     * Get the filters for the given request.
     *
     * @return \Illuminate\Support\Collection<int, \Laravel\Nova\Filters\Filter>
     */
    public function resolveFilters(NovaRequest $request): Collection
    {
        return collect(array_values($this->filter($this->filters($request))));
    }

    /**
     * Get the filters from filterable fields for the given request.
     */
    public function resolveFiltersFromFields(NovaRequest $request): Collection
    {
        return collect(array_values($this->filter(
            $this->filterableFields($request)
                ->transform(static fn ($field) => $field->resolveFilter($request)) /** @phpstan-ignore argument.type */
                ->filter()
                ->all()
        )));
    }

    /**
     * Get the filters available on the entity.
     *
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }
}
