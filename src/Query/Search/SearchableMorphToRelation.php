<?php

namespace Laravel\Nova\Query\Search;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Query\Expression;

/**
 * @method static static make(string $relation, \Illuminate\Contracts\Database\Query\Expression|string $column, array $types)
 * @method static static exact(string $relation, \Illuminate\Contracts\Database\Query\Expression|string $column, array $types)
 */
class SearchableMorphToRelation extends SearchableRelation
{
    /**
     * Construct a new search.
     *
     * @param  array<int, class-string<\Illuminate\Database\Eloquent\Model|\Laravel\Nova\Resource>|string>  $types
     */
    public function __construct(
        string $relation,
        Expression|string $column,
        public array $types = []
    ) {
        parent::__construct($relation, $column);
    }

    /** {@inheritDoc} */
    #[\Override]
    public function __invoke(Builder $query, string $search, string $connectionType, string $whereOperator = 'orWhere'): Builder
    {
        return $query->{$whereOperator.'HasMorph'}($this->relation, $this->morphTypes(), function ($query) use ($search, $connectionType) {
            return Column::from($this->column)->__invoke(
                $query, $search, $connectionType, 'where'
            );
        });
    }

    /**
     * Get available morph types.
     *
     * @return array<int, class-string<\Illuminate\Database\Eloquent\Model>|string>|string
     */
    protected function morphTypes(): array|string
    {
        if (empty($this->types)) {
            return '*';
        }

        return collect($this->types)
            ->map(static fn ($resource) => $resource::$model ?? $resource)
            ->all();
    }
}
