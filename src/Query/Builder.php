<?php

namespace Laravel\Nova\Query;

use Illuminate\Contracts\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Contracts\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Traits\Conditionable;
use Laravel\Nova\Contracts\QueryBuilder;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\TrashedStatus;
use Laravel\Scout\Builder as ScoutBuilder;
use Laravel\Scout\Contracts\PaginatesEloquentModels;
use RuntimeException;
use WeakMap;

class Builder implements QueryBuilder
{
    use Conditionable;

    /**
     * The original query builder instance.
     */
    protected ?EloquentBuilder $originalQueryBuilder = null;

    /**
     * The query builder instance.
     */
    protected EloquentBuilder|ScoutBuilder|null $queryBuilder = null;

    /**
     * Optional callbacks before model query execution.
     *
     * @var array<int, callable(\Illuminate\Contracts\Database\Eloquent\Builder):void>
     */
    protected array $queryCallbacks = [];

    /**
     * Determine query callbacks has been applied.
     */
    protected WeakMap $appliedQueryCallbacks;

    /**
     * Construct a new query builder for a resource.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     */
    public function __construct(
        protected readonly string $resourceClass
    ) {
        $this->appliedQueryCallbacks = new WeakMap;
    }

    /**
     * Build a "whereKey" query for the given resource.
     *
     * @return $this
     */
    public function whereKey(EloquentBuilder $query, string|int $key)
    {
        $this->setOriginalQueryBuilder($this->queryBuilder = $query);

        $this->tap(static function ($query) use ($key) {
            /** @var \Illuminate\Contracts\Database\Eloquent\Builder $query */
            $query->whereKey($key);
        });

        return $this;
    }

    /**
     * Build a "search" query for the given resource.
     *
     * @param  array<int, \Laravel\Nova\Query\ApplyFilter>  $filters
     * @param  array<string, string>  $orderings
     * @return $this
     */
    public function search(
        NovaRequest $request,
        EloquentBuilder $query,
        ?string $search = null,
        array $filters = [],
        array $orderings = [],
        TrashedStatus $withTrashed = TrashedStatus::DEFAULT
    ) {
        $this->setOriginalQueryBuilder($query);

        $hasSearchKeyword = ! empty(trim($search ?? ''));
        $hasOrderings = collect($orderings)->filter()->isNotEmpty();

        if ($this->resourceClass::usesScout()) {
            if ($hasSearchKeyword) {
                $this->queryBuilder = $this->resourceClass::buildIndexQueryUsingScout($request, $search, $withTrashed);
                $search = '';

                if ($query instanceof HasMany) {
                    $this->tap(function ($queryBuilder) use ($query) {
                        /** @var \Illuminate\Contracts\Database\Eloquent\Builder $queryBuilder */
                        $queryBuilder->whereIn(
                            $this->resourceClass::newModel()->getQualifiedKeyName(),
                            $query->select($query->getModel()->getKeyName())
                        );
                    });
                } elseif ($query instanceof MorphToMany || $query instanceof BelongsToMany) {
                    $this->tap(function ($queryBuilder) use ($query) {
                        /** @var \Illuminate\Contracts\Database\Eloquent\Builder $queryBuilder */
                        $queryBuilder->whereIn(
                            $this->resourceClass::newModel()->getQualifiedKeyName(),
                            $query->allRelatedIds()
                        );
                    });
                }
            }

            if (! $hasSearchKeyword && ! $hasOrderings) {
                $this->tap(function ($query) {
                    /** @var \Illuminate\Contracts\Database\Eloquent\Builder $query */
                    $this->resourceClass::defaultOrderings($query);
                });
            }
        }

        if (! isset($this->queryBuilder)) {
            $this->queryBuilder = $query;
        }

        $this->tap(function ($query) use ($request, $search, $filters, $orderings, $withTrashed) {
            /** @var \Illuminate\Contracts\Database\Eloquent\Builder $query */
            $this->resourceClass::buildIndexQuery(
                $request, $query, $search, $filters, $orderings, $withTrashed
            );
        });

        return $this;
    }

    /**
     * Pass the query to a given callback.
     *
     * @param  callable(\Illuminate\Contracts\Database\Eloquent\Builder):void  $callback
     * @return $this
     */
    public function tap(callable $callback)
    {
        $this->queryCallbacks[] = $callback;

        return $this;
    }

    /**
     * Set the "take" directly to Scout or Eloquent builder.
     *
     * @return $this
     */
    public function take(?int $limit)
    {
        $this->queryBuilder->take($limit);

        return $this;
    }

    /**
     * Defer setting a "limit" using query callback and only executed via Eloquent builder.
     *
     * @return $this
     */
    public function limit(?int $limit)
    {
        return $this->tap(static function ($query) use ($limit) {
            /** @var \Illuminate\Contracts\Database\Eloquent\Builder $query */
            $query->limit($limit);
        });
    }

    /**
     * Get the results of the search.
     */
    public function get(): EloquentCollection
    {
        return $this->applyQueryCallbacks($this->queryBuilder)->get();
    }

    /**
     * Get a lazy collection for the given query by chunks of the given size.
     */
    public function lazy(int $chunkSize = 1000): LazyCollection
    {
        return $this->applyQueryCallbacks($this->queryBuilder)->lazy($chunkSize);
    }

    /**
     * Get a lazy collection for the given query.
     */
    public function cursor(): LazyCollection
    {
        $queryBuilder = $this->applyQueryCallbacks($this->queryBuilder);

        if (! $queryBuilder instanceof ScoutBuilder && empty($queryBuilder->getEagerLoads())) {
            return $queryBuilder->cursor();
        }

        return $queryBuilder->get()->lazy();
    }

    /**
     * Get the paginated results of the query.
     *
     * @return array{0: \Illuminate\Contracts\Pagination\Paginator, 1: int|null, 2: bool}
     */
    public function paginate(int $perPage): array
    {
        $queryBuilder = $this->applyQueryCallbacks($this->queryBuilder);

        if (! $queryBuilder instanceof ScoutBuilder) {
            return [
                $queryBuilder->simplePaginate($perPage),
                $this->getCountForPagination(),
                true,
            ];
        }

        return $this->paginateFromScout($queryBuilder, $perPage);
    }

    /**
     * Get the paginated results of the Scout query.
     *
     * @return array{0: \Illuminate\Contracts\Pagination\Paginator, 1: int|null, 2: false}
     */
    protected function paginateFromScout(ScoutBuilder $queryBuilder, int $perPage): array
    {
        $originalQueryBuilder = clone $this->originalQueryBuilder;

        [$sql, $bindings] = [$originalQueryBuilder->toSql(), $originalQueryBuilder->getBindings()];

        $modelQueryBuilder = $this->handleQueryCallbacks($originalQueryBuilder);

        if ($sql === $modelQueryBuilder->toSql() && array_diff($bindings, $modelQueryBuilder->getBindings()) === []) {
            /** @var \Illuminate\Pagination\LengthAwarePaginator $paginated */
            $paginated = $queryBuilder->paginate($perPage);

            $items = $paginated->items();

            $hasMorePages = ($paginated->perPage() * $paginated->currentPage()) < $paginated->total();

            return [
                app()->makeWith(Paginator::class, [
                    'items' => $items,
                    'perPage' => $paginated->perPage(),
                    'currentPage' => $paginated->currentPage(),
                    'options' => $paginated->getOptions(),
                ])->hasMorePagesWhen($hasMorePages),
                $paginated->total(),
                false,
            ];
        }

        /** @var array<int, string|int> $scoutResultKeys */
        $scoutResultKeys = $queryBuilder->keys()->all();

        /** @var \Illuminate\Database\Eloquent\Model&\Laravel\Scout\Searchable $model */
        $model = $this->resourceClass::newModel();

        $paginated = tap($model->queryScoutModelsByIds(
            $queryBuilder, $scoutResultKeys
        ), function ($query) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $this->originalQueryBuilder = $query;
        })->simplePaginate($perPage);

        if (! $model->searchableUsing() instanceof PaginatesEloquentModels) {
            /** @var array<int|string, int> $objectIdPositions */
            $objectIdPositions = collect($scoutResultKeys)->values()->flip()->all();

            $paginated->setCollection(
                $paginated->getCollection()
                    ->sortBy(static fn ($model) => $objectIdPositions[$model->getScoutKey()], SORT_NUMERIC)
                    ->values()
            );
        }

        return [$paginated, $this->getCountForPagination(), false];
    }

    /**
     * Get the count of the total records for the paginator.
     */
    public function getCountForPagination(): ?int
    {
        return $this->toBaseQueryBuilder()->getCountForPagination();
    }

    /**
     * Convert the query builder to an Eloquent query builder (skip using Scout).
     */
    public function toBase(): EloquentBuilder
    {
        return $this->applyQueryCallbacks($this->originalQueryBuilder);
    }

    /**
     * Convert the query builder to an fluent query builder (skip using Scout).
     */
    public function toBaseQueryBuilder(): BaseBuilder
    {
        return $this->toBase()->toBase();
    }

    /**
     * Set original query builder instance.
     */
    protected function setOriginalQueryBuilder(EloquentBuilder $queryBuilder): void
    {
        if (isset($this->originalQueryBuilder)) {
            throw new RuntimeException('Unable to override $originalQueryBuilder, please create a new '.self::class);
        }

        $this->originalQueryBuilder = $queryBuilder;
    }

    /**
     * Apply any query callbacks to the query builder.
     */
    protected function applyQueryCallbacks(EloquentBuilder|ScoutBuilder $queryBuilder): EloquentBuilder|ScoutBuilder
    {
        return $this->appliedQueryCallbacks[$queryBuilder] ??= $this->handleQueryCallbacks($queryBuilder);
    }

    /**
     * Handle any query callbacks to the query builder.
     */
    protected function handleQueryCallbacks(EloquentBuilder|ScoutBuilder $queryBuilder): EloquentBuilder|ScoutBuilder
    {
        $callback = function ($query) {
            /** @var \Illuminate\Contracts\Database\Eloquent\Builder $query */
            collect($this->queryCallbacks)
                ->filter()
                ->each(static function ($callback) use ($query) {
                    \call_user_func($callback, $query);
                });
        };

        if ($queryBuilder instanceof ScoutBuilder) {
            $queryBuilder->query($callback);
        } else {
            $queryBuilder->tap($callback);
        }

        return $queryBuilder;
    }
}
