<?php

namespace Laravel\Nova\Metrics;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Http\Requests\NovaRequest;

abstract class Progress extends Metric
{
    use RoundingPrecision;

    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'progress-metric';

    /**
     * Return a progress result showing the growth of an count aggregate.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  callable(\Illuminate\Contracts\Database\Eloquent\Builder):(mixed)  $progress
     */
    public function count(
        NovaRequest $request,
        Builder|Model|string $model,
        callable $progress,
        Expression|string|null $column = null,
        int|float|null $target = null
    ): ProgressResult {
        return $this->aggregate($request, $model, 'count', $column, $progress, $target);
    }

    /**
     * Return a progress result showing the growth of a sum aggregate.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  callable(\Illuminate\Contracts\Database\Eloquent\Builder):(mixed)  $progress
     */
    public function sum(
        NovaRequest $request,
        Builder|Model|string $model,
        callable $progress,
        Expression|string $column,
        int|float|null $target = null
    ): ProgressResult {
        return $this->aggregate($request, $model, 'sum', $column, $progress, $target);
    }

    /**
     * Return a progress result showing the segments of a aggregate.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  callable(\Illuminate\Contracts\Database\Eloquent\Builder):(mixed)  $progress
     */
    protected function aggregate(
        NovaRequest $request,
        Builder|Model|string $model,
        string $function,
        Expression|string|null $column,
        callable $progress,
        int|float|null $target = null
    ): ProgressResult {
        $query = $model instanceof Builder ? $model : (new $model)->newQuery();

        $column ??= $query->getModel()->getQualifiedKeyName();

        $query->tap(
            fn ($query) => $this->applyFilterQuery($request, $query)
        );

        return $this->result(
            round(
                (clone $query)->tap(static function ($query) use ($progress) {
                    \call_user_func($progress, $query);
                })->{$function}($column) ?? 0,
                $this->roundingPrecision,
                $this->roundingMode
            ),
            $target ?? round(
                (clone $query)->{$function}($column) ?? 0,
                $this->roundingPrecision,
                $this->roundingMode
            )
        );
    }

    /**
     * Create a new progress metric result.
     *
     * @param  int|float  $value
     * @param  int|float  $target
     */
    public function result($value, $target): ProgressResult
    {
        return new ProgressResult($value, $target);
    }
}
