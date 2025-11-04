<?php

namespace Laravel\Nova\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\LazyCollection;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\TrashedStatus;

/**
 * @method $this tap(callable(\Illuminate\Contracts\Database\Eloquent\Builder):void $callback)
 */
interface QueryBuilder
{
    /**
     * Build a "whereKey" query for the given resource.
     *
     * @return $this
     */
    public function whereKey(Builder $query, string|int $key);

    /**
     * Build a "search" query for the given resource.
     *
     * @param  array<int, \Laravel\Nova\Query\ApplyFilter>  $filters
     * @param  array<string, string>  $orderings
     * @return $this
     */
    public function search(
        NovaRequest $request,
        Builder $query,
        ?string $search = null,
        array $filters = [],
        array $orderings = [],
        TrashedStatus $withTrashed = TrashedStatus::DEFAULT
    );

    /**
     * Set the "take" directly to Scout or Eloquent builder.
     *
     * @return $this
     */
    public function take(?int $limit);

    /**
     * Defer setting a "limit" using query callback and only executed via Eloquent builder.
     *
     * @return $this
     */
    public function limit(?int $limit);

    /**
     * Get the results of the search.
     */
    public function get(): EloquentCollection;

    /**
     * Get a lazy collection for the given query by chunks of the given size.
     */
    public function lazy(int $chunkSize = 1000): LazyCollection;

    /**
     * Get a lazy collection for the given query.
     */
    public function cursor(): LazyCollection;

    /**
     * Get the paginated results of the query.
     *
     * @return array{0: \Illuminate\Contracts\Pagination\Paginator, 1: int|null, 2: bool}
     */
    public function paginate(int $perPage): array;

    /**
     * Convert the query builder to an Eloquent query builder (skip using Scout).
     */
    public function toBase(): Builder;

    /**
     * Convert the query builder to fluent query builder (skip using Scout).
     */
    public function toBaseQueryBuilder(): BaseBuilder;
}
