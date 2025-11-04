<?php

namespace Laravel\Nova;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Query\Search;
use Laravel\Nova\Query\Search\PrimaryKey;
use Laravel\Scout\Builder as ScoutBuilder;

trait PerformsQueries
{
    /**
     * Build an "index" query for the given resource.
     *
     * @param  array<int, \Laravel\Nova\Query\ApplyFilter>  $filters
     * @param  array<string, string>  $orderings
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public static function buildIndexQuery(
        NovaRequest $request,
        Builder $query,
        ?string $search = null,
        array $filters = [],
        array $orderings = [],
        TrashedStatus $withTrashed = TrashedStatus::DEFAULT
    ) {
        return static::applyOrderings(static::applyFilters(
            $request, static::initializeQuery($request, $query, (string) $search, $withTrashed), $filters
        ), $orderings)->tap(static function ($query) use ($request) {
            static::indexQuery($request, $query->with(static::$with));
        });
    }

    /**
     * Initialize the given index query.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    protected static function initializeQuery(NovaRequest $request, Builder $query, string $search, TrashedStatus $withTrashed)
    {
        if (empty(trim($search))) {
            return static::applySoftDeleteConstraint($query, $withTrashed);
        }

        return static::usesScout()
                ? static::initializeQueryUsingScout($request, $query, $search, $withTrashed)
                : static::applySearch(static::applySoftDeleteConstraint($query, $withTrashed), $search);
    }

    /**
     * Apply the search query to the query.
     */
    protected static function applySearch(Builder $query, string $search): Builder
    {
        $modelKeyName = $query->getModel()->getKeyName();

        /** @phpstan-ignore nullCoalesce.expr */
        $searchColumns = collect(static::searchableColumns() ?? [])
                            ->transform(static function ($column) use ($modelKeyName) {
                                if ($column === $modelKeyName) {
                                    return new PrimaryKey($column, static::maxPrimaryKeySize());
                                }

                                return $column;
                            })->all();

        return static::initializeSearch($query, $search, $searchColumns);
    }

    /**
     * Initialize the search configuration.
     */
    protected static function initializeSearch(Builder $query, string $search, array $searchColumns): Builder
    {
        return app(Search::class, [
            'queryBuilder' => $query,
            'searchKeyword' => $search,
        ])->handle(__CLASS__, $searchColumns);
    }

    /**
     * Initialize the given index query using Laravel Scout.
     */
    protected static function initializeQueryUsingScout(NovaRequest $request, Builder $query, string $search, TrashedStatus $withTrashed): Builder
    {
        $keys = static::buildIndexQueryUsingScout($request, $search, $withTrashed)->get()->map->getKey();

        return static::applySoftDeleteConstraint(
            $query->whereIn(static::newModel()->getQualifiedKeyName(), $keys->all()), $withTrashed
        );
    }

    /**
     * Build an "index" result for the given resource using Scout.
     */
    public static function buildIndexQueryUsingScout(
        NovaRequest $request,
        ?string $search = null,
        TrashedStatus $withTrashed = TrashedStatus::DEFAULT
    ): ScoutBuilder {
        return tap(static::applySoftDeleteConstraint(
            static::newModel()->search($search), $withTrashed
        ), static function ($scoutBuilder) use ($request) {
            static::scoutQuery($request, $scoutBuilder);
        })->take(static::$scoutSearchResults);
    }

    /**
     * Scope the given query for the soft delete state.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Laravel\Scout\Builder  $query
     * @return \Illuminate\Contracts\Database\Eloquent\Builder|\Laravel\Scout\Builder
     */
    protected static function applySoftDeleteConstraint($query, TrashedStatus $withTrashed)
    {
        return static::softDeletes()
                ? $withTrashed->applySoftDeleteConstraint($query)
                : $query;
    }

    /**
     * Apply any applicable filters to the query.
     *
     * @param  array<int, \Laravel\Nova\Query\ApplyFilter>  $filters
     */
    protected static function applyFilters(NovaRequest $request, Builder $query, array $filters): Builder
    {
        collect($filters)->each->__invoke($request, $query);

        return $query;
    }

    /**
     * Apply any applicable orderings to the query.
     *
     * @param  array<string, string>  $orderings
     */
    protected static function applyOrderings(Builder $query, array $orderings): Builder
    {
        $orderings = array_filter($orderings);

        if (empty($orderings)) {
            return empty($query->getQuery()->orders) && ! static::usesScout()
                        ? static::defaultOrderings($query)
                        : $query;
        }

        foreach ($orderings as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    /**
     * Apply the default orderings for the given resource.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public static function defaultOrderings(Builder $query)
    {
        return $query->latest($query->getModel()->getQualifiedKeyName());
    }

    /**
     * Build an "index" query for the given resource.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, Builder $query)
    {
        return $query;
    }

    /**
     * Build a Scout search query for the given resource.
     *
     * @return \Laravel\Scout\Builder
     */
    public static function scoutQuery(NovaRequest $request, ScoutBuilder $query)
    {
        return $query;
    }

    /**
     * Build a "detail" query for the given resource.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public static function detailQuery(NovaRequest $request, Builder $query)
    {
        return $query;
    }

    /**
     * Build an "edit" query for the given resource.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public static function editQuery(NovaRequest $request, Builder $query)
    {
        return $query;
    }

    /**
     * Build a "replicate" query for the given resource.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public static function replicateQuery(NovaRequest $request, Builder $query)
    {
        return $query;
    }

    /**
     * Build a "relatable" query for the given resource.
     *
     * This query determines which instances of the model may be attached to other resources.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public static function relatableQuery(NovaRequest $request, Builder $query)
    {
        return $query;
    }
}
