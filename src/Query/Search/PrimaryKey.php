<?php

namespace Laravel\Nova\Query\Search;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Query\Expression;

use function Orchestra\Sidekick\Eloquent\model_key_type;

class PrimaryKey extends Column
{
    /**
     * Construct a new search.
     */
    public function __construct(
        Expression|string $column,
        public int $maxPrimaryKeySize = PHP_INT_MAX
    ) {
        parent::__construct($column);
    }

    /** {@inheritDoc} */
    #[\Override]
    public function __invoke(Builder $query, string|int $search, string $connectionType, string $whereOperator = 'orWhere'): Builder
    {
        $model = $query->getModel();

        $validIntegerKeyword = ctype_digit($search);
        $validIntegerOnModelKey = \in_array(model_key_type($model), ['int', 'integer']);

        if ($whereOperator === 'orWhere' && $validIntegerKeyword === false && $validIntegerOnModelKey === true) {
            return $query;
        } elseif (
            $validIntegerKeyword === true &&
            $validIntegerOnModelKey === true &&
            ($connectionType != 'pgsql' || $search <= $this->maxPrimaryKeySize)
        ) {
            return $query->{$whereOperator}($model->getQualifiedKeyName(), $search);
        }

        return parent::__invoke($query, $search, $connectionType, $whereOperator);
    }
}
