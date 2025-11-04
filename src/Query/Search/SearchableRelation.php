<?php

namespace Laravel\Nova\Query\Search;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Query\Expression;

/**
 * @method static static make(string $relation, \Illuminate\Contracts\Database\Query\Expression|string $column)
 * @method static static exact(string $relation, \Illuminate\Contracts\Database\Query\Expression|string $column)
 */
class SearchableRelation extends Column
{
    /**
     * Construct a new search.
     */
    public function __construct(
        public string $relation,
        Expression|string $column
    ) {
        parent::__construct($column);
    }

    /** {@inheritDoc} */
    #[\Override]
    public function __invoke(Builder $query, string $search, string $connectionType, string $whereOperator = 'orWhere'): Builder
    {
        return $query->{$whereOperator.'Has'}($this->relation, function ($query) use ($search, $connectionType) {
            $modelKeyName = $query->getModel()->getKeyName();

            $searchUsing = $modelKeyName === $this->column
                ? PrimaryKey::make($this->column)
                : Column::from($this->column);

            return $searchUsing(
                $query, $search, $connectionType, 'where'
            );
        });
    }
}
