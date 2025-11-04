<?php

namespace Laravel\Nova\Http\Requests;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use LogicException;

/**
 * @property-read string|array<int, mixed> $resources
 */
class LensResourceDeletionRequest extends NovaRequest
{
    use InteractsWithLenses;
    use QueriesResources;

    /**
     * Get the selected models for the action in chunks.
     *
     * @param  \Closure(\Illuminate\Support\Collection):void  $callback
     * @param  \Closure(\Illuminate\Support\Collection):\Illuminate\Support\Collection  $authCallback
     */
    protected function chunkWithAuthorization(int $count, Closure $callback, Closure $authCallback): void
    {
        $this->toSelectedResourceQuery()->when(! $this->allResourcesSelected(), function ($query) {
            $query->whereKey($this->resources);
        })->tap(static function ($query) {
            $query->getQuery()->orders = [];
        })->chunkById($count, static function ($models) use ($callback, $authCallback) {
            $models = $authCallback($models);

            if ($models->isNotEmpty()) {
                $callback($models);
            }
        });
    }

    /**
     * Get the query for the models that were selected by the user.
     */
    protected function toSelectedResourceQuery(): Builder
    {
        return $this->allResourcesSelected()
            ? $this->toQuery()
            : $this->newQueryWithoutScopes();
    }

    /**
     * Transform the request into a query.
     *
     * @throws \LogicException
     */
    public function toQuery(): Builder
    {
        return tap($this->lens()->query(LensRequest::createFrom($this), $this->newSearchQuery()), static function ($query) {
            if (! $query instanceof Builder) {
                throw new LogicException('Lens must return an Eloquent query instance in order to perform this action.');
            }
        });
    }
}
