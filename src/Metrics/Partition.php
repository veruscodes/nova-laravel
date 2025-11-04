<?php

namespace Laravel\Nova\Metrics;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;

abstract class Partition extends Metric
{
    use RoundingPrecision;

    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'partition-metric';

    /**
     * Return a partition result showing the segments of a count aggregate.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function count(NovaRequest $request, Builder|Model|string $model, Expression|string $groupBy, Expression|string|null $column = null): PartitionResult
    {
        return $this->aggregate($request, $model, 'count', $column, $groupBy);
    }

    /**
     * Return a partition result showing the segments of an average aggregate.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function average(NovaRequest $request, Builder|Model|string $model, Expression|string|null $column, Expression|string $groupBy): PartitionResult
    {
        return $this->aggregate($request, $model, 'avg', $column, $groupBy);
    }

    /**
     * Return a partition result showing the segments of a sum aggregate.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function sum(NovaRequest $request, Builder|Model|string $model, Expression|string|null $column, Expression|string $groupBy): PartitionResult
    {
        return $this->aggregate($request, $model, 'sum', $column, $groupBy);
    }

    /**
     * Return a partition result showing the segments of a max aggregate.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function max(NovaRequest $request, Builder|Model|string $model, Expression|string|null $column, Expression|string $groupBy): PartitionResult
    {
        return $this->aggregate($request, $model, 'max', $column, $groupBy);
    }

    /**
     * Return a partition result showing the segments of a min aggregate.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function min(NovaRequest $request, Builder|Model|string $model, Expression|string|null $column, Expression|string $groupBy): PartitionResult
    {
        return $this->aggregate($request, $model, 'min', $column, $groupBy);
    }

    /**
     * Return a partition result showing the segments of a aggregate.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    protected function aggregate(
        NovaRequest $request,
        Builder|Model|string $model,
        string $function,
        Expression|string|null $column,
        Expression|string $groupBy
    ): PartitionResult {
        $query = $model instanceof Builder ? $model : (new $model)->newQuery();
        $grammar = $query->getQuery()->getGrammar();

        $wrappedColumn = $grammar->wrap($column ?? $query->getModel()->getQualifiedKeyName());
        $wrappedGroupByLabel = $grammar->wrap($groupBy);

        $results = $query->select(
            DB::raw("{$wrappedGroupByLabel} as aggregate_group_by_label"),
            DB::raw("{$function}({$wrappedColumn}) as aggregate")
        )->tap(function ($query) use ($request) {
            return $this->applyFilterQuery($request, $query);
        })->groupBy($groupBy)->get();

        return $this->result($results->mapWithKeys(function ($result) {
            return $this->formatAggregateResult(
                $result,
            );
        })->all());
    }

    /**
     * Format the aggregate result for the partition.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $result
     * @return array<string|int, int|float>
     */
    protected function formatAggregateResult($result)
    {
        /** @phpstan-ignore property.notFound */
        $key = $result->aggregate_group_by_label;

        if (! \is_int($key) && ! \is_bool($key)) {
            $key = (string) $key;
        }

        return [$key => $result->aggregate ?? 0];
    }

    /**
     * Create a new partition metric result.
     *
     * @param  array<string, int|float>  $value
     */
    public function result(array $value): PartitionResult
    {
        return new PartitionResult(collect($value)->map(function ($number) {
            return round($number, $this->roundingPrecision, $this->roundingMode);
        })->toArray());
    }
}
