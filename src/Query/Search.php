<?php

namespace Laravel\Nova\Query;

use Illuminate\Contracts\Database\Eloquent\Builder as EloquentBuilder;
use Laravel\Nova\Query\Search\Column;

class Search
{
    /**
     * Create a new search builder instance.
     */
    public function __construct(
        public EloquentBuilder $queryBuilder,
        public string $searchKeyword
    ) {
        //
    }

    /**
     * Get the raw results of the search.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     * @param  array<int, string|\Laravel\Nova\Query\Search\Column>  $searchColumns
     */
    public function handle(string $resourceClass, array $searchColumns): EloquentBuilder
    {
        return $this->queryBuilder->where(function ($query) use ($searchColumns) {
            $connectionType = $query->getModel()->getConnection()->getDriverName();

            $columns = collect($searchColumns);

            $whereOperator = $columns->count() > 1 ? 'orWhere' : 'where';

            $columns->each(function ($column) use ($query, $connectionType, $whereOperator) {
                /** @phpstan-ignore booleanAnd.alwaysFalse */
                if ($column instanceof Column || (! \is_string($column) && \is_callable($column))) {
                    $column($query, $this->searchKeyword, $connectionType, $whereOperator);
                } else {
                    Column::from($column)->__invoke($query, $this->searchKeyword, $connectionType, $whereOperator);
                }
            });
        });
    }
}
