<?php

namespace Laravel\Nova\Query;

use Illuminate\Contracts\Database\Eloquent\Builder as EloquentBuilder;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class ApplyFilter
{
    /**
     * Create a new invokable filter applier.
     */
    public function __construct(
        public Filter $filter,
        public mixed $value
    ) {
        //
    }

    /**
     * Apply the filter to the given query.
     */
    public function __invoke(NovaRequest $request, EloquentBuilder $query): EloquentBuilder
    {
        $this->filter->apply(
            $request, $query, $this->value
        );

        return $query;
    }
}
