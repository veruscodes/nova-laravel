<?php

namespace Laravel\Nova\Query\Search;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Query\Expression as ExpressionContract;
use Illuminate\Database\Query\Expression;
use Laravel\Nova\Makeable;

/**
 * @method static static make(\Illuminate\Contracts\Database\Query\Expression|string $column)
 * @method static static exact(\Illuminate\Contracts\Database\Query\Expression|string $column)
 */
class Column
{
    use Makeable;

    /**
     * Query where operator type.
     */
    protected SearchType $searchType = SearchType::LIKE;

    /**
     * Construct a new search.
     */
    public function __construct(public ExpressionContract|string $column)
    {
        //
    }

    /**
     * Create a new instance with exact search.
     *
     * @return static
     *
     * @phpstan-ignore missingType.parameter
     */
    public static function exact(...$arguments)
    {
        return static::make(...$arguments)->whereUsing(SearchType::EXACT);
    }

    /**
     * Create Column instance for raw expression value.
     */
    public static function raw(string $column): static
    {
        return new static(new Expression($column));
    }

    /**
     * Create Column instance from raw expression or fluent string.
     *
     * @param  \Illuminate\Database\Query\Expression|string  $column
     */
    public static function from(ExpressionContract|string $column): static|SearchableJson|SearchableRelation
    {
        if ($column instanceof ExpressionContract) {
            return new static($column);
        }

        if (strpos($column, '->') !== false) {
            return SearchableJson::make($column);
        } elseif (strpos($column, '.') !== false) {
            [$relation, $columnName] = explode('.', $column, 2);

            return SearchableRelation::make($relation, $columnName);
        }

        return new static($column);
    }

    /**
     * Determine if query should use `=` operator.
     *
     * @return $this
     */
    public function whereUsing(SearchType $searchType)
    {
        $this->searchType = $searchType;

        return $this;
    }

    /**
     * Apply the search.
     */
    public function __invoke(Builder $query, string $search, string $connectionType, string $whereOperator = 'orWhere'): Builder
    {
        return $query->{$whereOperator}(
            $this->columnName($query),
            $this->resolveWhereOperatorFrom($connectionType),
            "%{$search}%"
        );
    }

    /**
     * Resolve where operator default.
     */
    protected function resolveWhereOperatorFrom(string $connectionType): string
    {
        $likeOperator = $connectionType === 'pgsql' ? 'ilike' : 'like';

        return match ($this->searchType) {
            SearchType::EXACT => '=',
            default => $likeOperator,
        };
    }

    /**
     * Get the column name.
     */
    protected function columnName(Builder $query): ExpressionContract|string
    {
        return $this->column instanceof ExpressionContract ? $this->column : $query->qualifyColumn($this->column);
    }
}
