<?php

declare(strict_types=1);

namespace Laravel\Nova\Filters;

use Laravel\Nova\Http\Requests\NovaRequest;

use function Orchestra\Sidekick\is_safe_callable;

trait Searchable
{
    /**
     * Indicates if this filter is searchable.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool
     */
    public $searchable = false;

    /**
     * Specify if this filter should be searchable.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool  $searchable
     * @return $this
     */
    public function searchable(callable|bool $searchable = true)
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Determine if the filter is searchable.
     */
    public function isSearchable(NovaRequest $request): bool
    {
        if (is_safe_callable($this->searchable)) {
            $this->searchable = \call_user_func($this->searchable, $request);
        }

        return $this->searchable;
    }
}
