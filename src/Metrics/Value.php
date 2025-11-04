<?php

namespace Laravel\Nova\Metrics;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\WithIcon;

abstract class Value extends RangedMetric
{
    use RoundingPrecision;
    use WithIcon;

    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'value-metric';

    /**
     * The element's icon.
     *
     * @var string
     */
    public $icon = 'chart-bar';

    /**
     * Set the icon for the metric.
     *
     * @return $this
     */
    public function icon(string $icon)
    {
        return $this->withIcon($icon);
    }

    /**
     * Return a value result showing the growth of an count aggregate over time.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function count(NovaRequest $request, Builder|Model|string $model, Expression|string|null $column = null, ?string $dateColumn = null): ValueResult
    {
        return $this->aggregate($request, $model, 'count', $column, $dateColumn);
    }

    /**
     * Return a value result showing the growth of an average aggregate over time.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function average(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): ValueResult
    {
        return $this->aggregate($request, $model, 'avg', $column, $dateColumn);
    }

    /**
     * Return a value result showing the growth of a sum aggregate over time.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function sum(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): ValueResult
    {
        return $this->aggregate($request, $model, 'sum', $column, $dateColumn);
    }

    /**
     * Return a value result showing the growth of a maximum aggregate over time.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function max(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): ValueResult
    {
        return $this->aggregate($request, $model, 'max', $column, $dateColumn);
    }

    /**
     * Return a value result showing the growth of a minimum aggregate over time.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function min(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): ValueResult
    {
        return $this->aggregate($request, $model, 'min', $column, $dateColumn);
    }

    /**
     * Return a value result showing the growth of a model over a given time frame.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    protected function aggregate(
        NovaRequest $request,
        Builder|Model|string $model,
        string $function,
        Expression|string|null $column = null,
        ?string $dateColumn = null
    ): ValueResult {
        $query = $model instanceof Builder ? $model : (new $model)->newQuery();

        $query->tap(
            fn ($query) => $this->applyFilterQuery($request, $query)
        );

        $column ??= $query->getModel()->getQualifiedKeyName();

        if ($request->range === 'ALL') {
            return $this->result(
                round(
                    (clone $query)->{$function}($column) ?? 0,
                    $this->roundingPrecision,
                    $this->roundingMode
                )
            );
        }

        $dateColumn ??= $query->getModel()->getQualifiedCreatedAtColumn();
        $timezone = Nova::resolveUserTimezone($request) ?? $this->getDefaultTimezone($request);
        $range = $request->range ?? 1;

        $currentRange = $this->currentRange($range, $timezone);
        $previousRange = $this->previousRange($range, $timezone);

        $previousValue = round(
            (clone $query)->whereBetween(
                $dateColumn, $this->formatQueryDateBetween($previousRange)
            )->{$function}($column) ?? 0,
            $this->roundingPrecision,
            $this->roundingMode
        );

        return $this->result(
            round(
                (clone $query)->whereBetween(
                    $dateColumn, $this->formatQueryDateBetween($currentRange)
                )->{$function}($column) ?? 0,
                $this->roundingPrecision,
                $this->roundingMode
            )
        )->previous($previousValue);
    }

    /**
     * Calculate the previous range and calculate any short-cuts.
     *
     * @return array<int, \Carbon\CarbonInterface>
     */
    protected function previousRange(string|int $range, string $timezone): array
    {
        if ($range == 'TODAY') {
            return [
                CarbonImmutable::now($timezone)->subDay()->startOfDay(),
                CarbonImmutable::now($timezone)->subDay()->endOfDay(),
            ];
        }

        if ($range == 'YESTERDAY') {
            return [
                CarbonImmutable::now($timezone)->subDays(2)->startOfDay(),
                CarbonImmutable::now($timezone)->subDays(2)->endOfDay(),
            ];
        }

        if ($range == 'THIS_WEEK') {
            return [
                CarbonImmutable::now($timezone)->subWeek()->startOfWeek(),
                CarbonImmutable::now($timezone)->subWeek()->endOfWeek(),
            ];
        }

        if ($range == 'MTD') {
            return [
                CarbonImmutable::now($timezone)->subMonthWithoutOverflow()->startOfMonth(),
                CarbonImmutable::now($timezone)->subMonthWithoutOverflow(),
            ];
        }

        if ($range == 'QTD') {
            return $this->previousQuarterRange($timezone);
        }

        if ($range == 'YTD') {
            return [
                CarbonImmutable::now($timezone)->subYear()->startOfYear(),
                CarbonImmutable::now($timezone)->subYear(),
            ];
        }

        return [
            CarbonImmutable::now($timezone)->subDays($range * 2),
            CarbonImmutable::now($timezone)->subDays($range)->subSecond(),
        ];
    }

    /**
     * Calculate the previous quarter range.
     *
     * @return array<int, \Carbon\CarbonImmutable>
     */
    protected function previousQuarterRange(string $timezone): array
    {
        return [
            CarbonImmutable::now($timezone)->subQuarterWithoutOverflow()->startOfQuarter(),
            CarbonImmutable::now($timezone)->subQuarterWithoutOverflow()->subSecond(),
        ];
    }

    /**
     * Calculate the current range and calculate any short-cuts.
     *
     * @return array<int, \Carbon\CarbonInterface>
     */
    protected function currentRange(string|int $range, string $timezone): array
    {
        if ($range == 'TODAY') {
            return [
                CarbonImmutable::now($timezone)->startOfDay(),
                CarbonImmutable::now($timezone)->endOfDay(),
            ];
        }

        if ($range == 'YESTERDAY') {
            return [
                CarbonImmutable::now($timezone)->subDay()->startOfDay(),
                CarbonImmutable::now($timezone)->subDay()->endOfDay(),
            ];
        }

        if ($range == 'THIS_WEEK') {
            return [
                CarbonImmutable::now($timezone)->startOfWeek(),
                CarbonImmutable::now($timezone)->endOfWeek(),
            ];
        }

        if ($range == 'MTD') {
            return [
                CarbonImmutable::now($timezone)->startOfMonth(),
                CarbonImmutable::now($timezone),
            ];
        }

        if ($range == 'QTD') {
            return $this->currentQuarterRange($timezone);
        }

        if ($range == 'YTD') {
            return [
                CarbonImmutable::now($timezone)->startOfYear(),
                CarbonImmutable::now($timezone),
            ];
        }

        return [
            CarbonImmutable::now($timezone)->subDays($range),
            CarbonImmutable::now($timezone),
        ];
    }

    /**
     * Calculate the previous quarter range.
     *
     * @return array<int, \Carbon\CarbonImmutable>
     */
    protected function currentQuarterRange(string $timezone): array
    {
        return [
            CarbonImmutable::now($timezone)->startOfQuarter(),
            CarbonImmutable::now($timezone),
        ];
    }

    /**
     * Create a new value metric result.
     *
     * @param  int|float|numeric-string|null  $value
     */
    public function result($value): ValueResult
    {
        return new ValueResult($value);
    }

    /**
     * Get default timezone.
     */
    private function getDefaultTimezone(Request $request): ?string
    {
        return $request->timezone ?? config('app.timezone');
    }

    /**
     * Prepare the metric for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'icon' => $this->icon,
        ]);
    }
}
