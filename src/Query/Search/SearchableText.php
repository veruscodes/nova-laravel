<?php

namespace Laravel\Nova\Query\Search;

use Illuminate\Contracts\Database\Eloquent\Builder;

class SearchableText extends Column
{
    /** {@inheritDoc} */
    #[\Override]
    public function __invoke(Builder $query, string $search, string $connectionType, string $whereOperator = 'orWhere'): Builder
    {
        if (\in_array($connectionType, ['mariadb', 'mysql', 'pgsql'])) {
            $query->{$whereOperator.'FullText'}(
                $this->columnName($query), $search
            );
        }

        return $query;
    }
}
