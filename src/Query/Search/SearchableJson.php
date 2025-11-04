<?php

namespace Laravel\Nova\Query\Search;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Str;

/**
 * @method static static make(\Illuminate\Contracts\Database\Query\Expression|string $jsonSelectorPath)
 * @method static static exact(\Illuminate\Contracts\Database\Query\Expression|string $jsonSelectorPath)
 */
class SearchableJson extends Column
{
    /**
     * Construct a new search.
     */
    public function __construct(public Expression|string $jsonSelectorPath)
    {
        //
    }

    /** {@inheritDoc} */
    #[\Override]
    public function __invoke(Builder $query, string $search, string $connectionType, string $whereOperator = 'orWhere'): Builder
    {
        $path = $query->getGrammar()->wrap($this->jsonSelectorPath);
        $likeOperator = $this->resolveWhereOperatorFrom($connectionType);

        if (\in_array($connectionType, ['pgsql', 'sqlite'])) {
            return $query->{$whereOperator}($this->jsonSelectorPath, $likeOperator, "%{$search}%");
        }

        return $query->{$whereOperator.'Raw'}("lower({$path}) {$likeOperator} ?", ['%'.Str::lower($search).'%']);
    }
}
