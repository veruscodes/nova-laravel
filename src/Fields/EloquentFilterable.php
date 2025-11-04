<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Fields\Filters\EloquentFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

trait EloquentFilterable
{
    use Filterable;

    /**
     * Make the field filter.
     *
     * @return \Laravel\Nova\Fields\Filters\EloquentFilter|null
     */
    protected function makeFilter(NovaRequest $request)
    {
        return new EloquentFilter($this);
    }

    /**
     * Define filterable attribute.
     *
     * @return string
     */
    abstract protected function filterableAttribute(NovaRequest $request);

    /**
     * Define the default filterable callback.
     *
     * @return callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Contracts\Database\Eloquent\Builder, mixed, string):void
     */
    abstract protected function defaultFilterableCallback();
}
