<?php

namespace Laravel\Nova\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

interface Filter
{
    /**
     * Get the key for the filter.
     *
     * @return string
     */
    public function key();

    /**
     * Apply the filter to the given query.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, Builder $query, mixed $value);

    /**
     * Determine if the filter should be available for the given request.
     *
     * @return bool
     */
    public function authorizedToSee(Request $request);
}
