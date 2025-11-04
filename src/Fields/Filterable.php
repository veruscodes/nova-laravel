<?php

namespace Laravel\Nova\Fields;

use Closure;
use InvalidArgumentException;
use Laravel\Nova\Http\Requests\NovaRequest;

trait Filterable
{
    /**
     * The callback used to determine if the field is filterable.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Contracts\Database\Eloquent\Builder, mixed, string):(void))|null
     */
    public $filterableCallback;

    /**
     * The callback used to determine if the field is filterable.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Contracts\Database\Eloquent\Builder, mixed, string):(void))|null  $filterableCallback
     * @return $this
     */
    public function filterable(?callable $filterableCallback = null)
    {
        if (property_exists($this, 'requiresExplicitFilterableCallback')
            && $this->requiresExplicitFilterableCallback === true
            && \is_null($filterableCallback)
        ) {
            throw new InvalidArgumentException('$filterableCallback needs to be callable/Closure');
        }

        $this->filterableCallback = ! \is_null($filterableCallback)
            ? $filterableCallback
            : $this->defaultFilterableCallback();

        return $this;
    }

    /**
     * Set field as without filterable.
     *
     * @return $this
     */
    public function withoutFilterable()
    {
        $this->filterableCallback = null;

        return $this;
    }

    /**
     * Apply the filter to the given query.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder  $query
     */
    public function applyFilter(NovaRequest $request, $query, mixed $value): void
    {
        $callback = $this->filterableCallback instanceof Closure
            ? Closure::bind($this->filterableCallback, $this)
            : $this->filterableCallback;

        \call_user_func($callback, $request, $query, $value, $this->filterableAttribute($request));
    }

    /**
     * Prepare the field for JSON serialization.
     */
    public function serializeForFilter(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * Make the field filter.
     *
     * @return \Laravel\Nova\Fields\Filters\Filter|null
     */
    public function resolveFilter(NovaRequest $request)
    {
        return \is_callable($this->filterableCallback) ? $this->makeFilter($request) : null;
    }

    /**
     * Define the default filterable callback.
     *
     * @return callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Contracts\Database\Eloquent\Builder, mixed, string):\Illuminate\Contracts\Database\Eloquent\Builder
     */
    protected function defaultFilterableCallback()
    {
        return function (NovaRequest $request, $query, $value, $attribute) {
            return $query->where($attribute, '=', $value);
        };
    }

    /**
     * Define filterable attribute.
     *
     * @return string
     */
    protected function filterableAttribute(NovaRequest $request)
    {
        return $this->attribute;
    }

    /**
     * Make the field filter.
     *
     * @return \Laravel\Nova\Fields\Filters\Filter|null
     */
    abstract protected function makeFilter(NovaRequest $request);
}
