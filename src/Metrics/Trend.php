<?php

namespace Laravel\Nova\Metrics;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTime;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

abstract class Trend extends RangedMetric
{
    use RoundingPrecision;

    /**
     * Trend metric unit constants.
     */
    public const BY_MONTHS = 'month';

    public const BY_WEEKS = 'week';

    public const BY_DAYS = 'day';

    public const BY_HOURS = 'hour';

    public const BY_MINUTES = 'minute';

    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'trend-metric';

    /**
     * Create a new trend metric result.
     *
     * @param  int|float|numeric-string|null  $value
     */
    public function result($value = null): TrendResult
    {
        return new TrendResult($value);
    }

    /**
     * Return a value result showing a count aggregate over months.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @return \Laravel\Nova\Metrics\TrendResult
     */
    public function countByMonths(NovaRequest $request, Builder|Model|string $model, Expression|string|null $column = null)
    {
        return $this->count($request, $model, self::BY_MONTHS, $column);
    }

    /**
     * Return a value result showing a count aggregate over weeks.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @return \Laravel\Nova\Metrics\TrendResult
     */
    public function countByWeeks(NovaRequest $request, Builder|Model|string $model, Expression|string|null $column = null)
    {
        return $this->count($request, $model, self::BY_WEEKS, $column);
    }

    /**
     * Return a value result showing a count aggregate over days.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @return \Laravel\Nova\Metrics\TrendResult
     */
    public function countByDays(NovaRequest $request, Builder|Model|string $model, Expression|string|null $column = null)
    {
        return $this->count($request, $model, self::BY_DAYS, $column);
    }

    /**
     * Return a value result showing a count aggregate over hours.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @return \Laravel\Nova\Metrics\TrendResult
     */
    public function countByHours(NovaRequest $request, Builder|Model|string $model, Expression|string|null $column = null)
    {
        return $this->count($request, $model, self::BY_HOURS, $column);
    }

    /**
     * Return a value result showing a count aggregate over minutes.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @return \Laravel\Nova\Metrics\TrendResult
     */
    public function countByMinutes(NovaRequest $request, Builder|Model|string $model, Expression|string|null $column = null)
    {
        return $this->count($request, $model, self::BY_MINUTES, $column);
    }

    /**
     * Return a value result showing a count aggregate over time.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function count(NovaRequest $request, Builder|Model|string $model, string $unit, Expression|string|null $column = null): TrendResult
    {
        $resource = $model instanceof Builder ? $model->getModel() : new $model;

        $column ??= $resource->getQualifiedCreatedAtColumn();

        return $this->aggregate($request, $model, $unit, 'count', $resource->getQualifiedKeyName(), $column);
    }

    /**
     * Return a value result showing a average aggregate over months.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function averageByMonths(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_MONTHS, 'avg', $column, $dateColumn);
    }

    /**
     * Return a value result showing a average aggregate over weeks.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function averageByWeeks(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_WEEKS, 'avg', $column, $dateColumn);
    }

    /**
     * Return a value result showing a average aggregate over days.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function averageByDays(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_DAYS, 'avg', $column, $dateColumn);
    }

    /**
     * Return a value result showing a average aggregate over hours.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function averageByHours(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_HOURS, 'avg', $column, $dateColumn);
    }

    /**
     * Return a value result showing a average aggregate over minutes.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function averageByMinutes(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_MINUTES, 'avg', $column, $dateColumn);
    }

    /**
     * Return a value result showing a average aggregate over time.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function average(NovaRequest $request, Builder|Model|string $model, string $unit, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, $unit, 'avg', $column, $dateColumn);
    }

    /**
     * Return a value result showing a sum aggregate over months.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function sumByMonths(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_MONTHS, 'sum', $column, $dateColumn);
    }

    /**
     * Return a value result showing a sum aggregate over weeks.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function sumByWeeks(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_WEEKS, 'sum', $column, $dateColumn);
    }

    /**
     * Return a value result showing a sum aggregate over days.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function sumByDays(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_DAYS, 'sum', $column, $dateColumn);
    }

    /**
     * Return a value result showing a sum aggregate over hours.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function sumByHours(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_HOURS, 'sum', $column, $dateColumn);
    }

    /**
     * Return a value result showing a sum aggregate over minutes.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function sumByMinutes(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_MINUTES, 'sum', $column, $dateColumn);
    }

    /**
     * Return a value result showing a sum aggregate over time.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function sum(NovaRequest $request, Builder|Model|string $model, string $unit, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, $unit, 'sum', $column, $dateColumn);
    }

    /**
     * Return a value result showing a max aggregate over months.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function maxByMonths(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_MONTHS, 'max', $column, $dateColumn);
    }

    /**
     * Return a value result showing a max aggregate over weeks.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function maxByWeeks(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_WEEKS, 'max', $column, $dateColumn);
    }

    /**
     * Return a value result showing a max aggregate over days.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function maxByDays(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_DAYS, 'max', $column, $dateColumn);
    }

    /**
     * Return a value result showing a max aggregate over hours.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function maxByHours(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_HOURS, 'max', $column, $dateColumn);
    }

    /**
     * Return a value result showing a max aggregate over minutes.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function maxByMinutes(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_MINUTES, 'max', $column, $dateColumn);
    }

    /**
     * Return a value result showing a max aggregate over time.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function max(NovaRequest $request, Builder|Model|string $model, string $unit, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, $unit, 'max', $column, $dateColumn);
    }

    /**
     * Return a value result showing a min aggregate over months.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function minByMonths(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_MONTHS, 'min', $column, $dateColumn);
    }

    /**
     * Return a value result showing a min aggregate over weeks.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function minByWeeks(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_WEEKS, 'min', $column, $dateColumn);
    }

    /**
     * Return a value result showing a min aggregate over days.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function minByDays(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_DAYS, 'min', $column, $dateColumn);
    }

    /**
     * Return a value result showing a min aggregate over hours.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function minByHours(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_HOURS, 'min', $column, $dateColumn);
    }

    /**
     * Return a value result showing a min aggregate over minutes.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function minByMinutes(NovaRequest $request, Builder|Model|string $model, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, self::BY_MINUTES, 'min', $column, $dateColumn);
    }

    /**
     * Return a value result showing a min aggregate over time.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function min(NovaRequest $request, Builder|Model|string $model, string $unit, Expression|string $column, ?string $dateColumn = null): TrendResult
    {
        return $this->aggregate($request, $model, $unit, 'min', $column, $dateColumn);
    }

    /**
     * Return a value result showing a aggregate over time.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    protected function aggregate(
        NovaRequest $request,
        Builder|Model|string $model,
        string $unit,
        string $function,
        Expression|string|null $column,
        ?string $dateColumn
    ): TrendResult {
        $query = $model instanceof Builder ? $model : (new $model)->newQuery();

        $timezone = Nova::resolveUserTimezone($request) ?? $this->getDefaultTimezone($request);

        $expression = (string) TrendDateExpressionFactory::make(
            $query, $dateColumn ??= $query->getModel()->getQualifiedCreatedAtColumn(),
            $unit, $timezone
        );

        $possibleDateResults = $this->getAllPossibleDateResults(
            $startingDate = $this->getAggregateStartingDate($request, $unit, $timezone),
            $endingDate = $this->getAggregateEndingDate($timezone),
            $unit,
            $request->twelveHourTime === 'true',
            $request->range
        );

        $wrappedColumn = $query->getQuery()->getGrammar()->wrap($column);

        $results = $query
                ->select(DB::raw("{$expression} as date_result, {$function}({$wrappedColumn}) as aggregate"))
                ->tap(fn ($query) => $this->applyFilterQuery($request, $query))
                ->whereBetween($dateColumn, $this->formatQueryDateBetween([$startingDate, $endingDate]))
                ->groupBy(DB::raw($expression))
                ->orderBy('date_result')
                ->get();

        $possibleDateKeys = array_keys($possibleDateResults);

        $results = array_merge(
            $possibleDateResults,
            $results->mapWithKeys(fn ($result) => [
                $this->formatAggregateResultDate(
                    $result->date_result, // @phpstan-ignore property.notFound
                    $unit,
                    $request->twelveHourTime === 'true'
                ) => round($result->aggregate ?? 0, $this->roundingPrecision, $this->roundingMode),
            ])->reject(static fn ($value, $key) => ! \in_array($key, $possibleDateKeys))
            ->all()
        );

        return $this->result(Arr::last($results))->trend(
            $results
        );
    }

    /**
     * Determine the proper aggregate starting date.
     *
     * @throws \InvalidArgumentException
     */
    protected function getAggregateStartingDate(NovaRequest $request, string $unit, ?string $timezone): CarbonInterface
    {
        $now = CarbonImmutable::now($timezone);

        $range = $request->range ?? 1;
        $ranges = collect($this->ranges())->keys()->values()->all();

        if (\count($ranges) > 0 && ! \in_array($range, $ranges)) {
            $range = min($range, max($ranges));
        }

        switch ($unit) {
            case 'month':
                return $now->subMonthsWithoutOverflow($range - 1)->firstOfMonth()->setTime(0, 0);

            case 'week':
                return $now->subWeeks($range - 1)->startOfWeek()->setTime(0, 0);

            case 'day':
                return $now->subDays($range - 1)->setTime(0, 0);

            case 'hour':
                return with($now->subHours($range - 1), static function ($now) {
                    return $now->setTimeFromTimeString($now->hour.':00');
                });

            case 'minute':
                return with($now->subMinutes($range - 1), static function ($now) {
                    return $now->setTimeFromTimeString($now->hour.':'.$now->minute.':00');
                });

            default:
                throw new InvalidArgumentException('Invalid trend unit provided.');
        }
    }

    /**
     * Determine the proper aggregate ending date.
     */
    protected function getAggregateEndingDate(?string $timezone): CarbonInterface
    {
        return CarbonImmutable::now($timezone);
    }

    /**
     * Format the aggregate result date into a proper string.
     */
    protected function formatAggregateResultDate(string $result, string $unit, bool $twelveHourTime): string
    {
        switch ($unit) {
            case 'month':
                return $this->formatAggregateMonthDate($result);

            case 'week':
                return $this->formatAggregateWeekDate($result);

            case 'day':
                return with(Carbon::createFromFormat('Y-m-d', $result), static function ($date) {
                    return Nova::__($date->format('F')).' '.$date->format('j').', '.$date->format('Y');
                });

            case 'hour':
                return with(Carbon::createFromFormat('Y-m-d H:00', $result), static function ($date) use ($twelveHourTime) {
                    return $twelveHourTime
                            ? Nova::__($date->format('F')).' '.$date->format('j').' - '.$date->format('g:00 A')
                            : Nova::__($date->format('F')).' '.$date->format('j').' - '.$date->format('G:00');
                });

            case 'minute':
            default:
                return with(Carbon::createFromFormat('Y-m-d H:i:00', $result), static function ($date) use ($twelveHourTime) {
                    return $twelveHourTime
                            ? Nova::__($date->format('F')).' '.$date->format('j').' - '.$date->format('g:i A')
                            : Nova::__($date->format('F')).' '.$date->format('j').' - '.$date->format('G:i');
                });
        }
    }

    /**
     * Format the aggregate month result date into a proper string.
     *
     * @param  string  $result
     * @return string
     */
    protected function formatAggregateMonthDate($result)
    {
        [$year, $month] = explode('-', $result);

        return with(Carbon::create((int) $year, (int) $month, 1), static function ($date) {
            return Nova::__($date->format('F')).' '.$date->format('Y');
        });
    }

    /**
     * Format the aggregate week result date into a proper string.
     *
     * @param  string  $result
     * @return string
     */
    protected function formatAggregateWeekDate($result)
    {
        [$year, $week] = explode('-', $result);

        $isoDate = (new DateTime)->setISODate((int) $year, (int) $week)->setTime(0, 0);

        [$startingDate, $endingDate] = [
            Carbon::instance($isoDate),
            Carbon::instance($isoDate)->endOfWeek(),
        ];

        return Nova::__($startingDate->format('F')).' '.$startingDate->format('j').' - '.
               Nova::__($endingDate->format('F')).' '.$endingDate->format('j');
    }

    /**
     * Get all of the possible date results for the given units.
     *
     * @return array<string, int>
     */
    protected function getAllPossibleDateResults(
        CarbonInterface $startingDate,
        CarbonInterface $endingDate,
        string $unit,
        bool $twelveHourTime,
        ?int $possibleDateRange
    ): array {
        $nextDate = Carbon::instance($startingDate);

        do {
            $possibleDateResults[
                $this->formatPossibleAggregateResultDate(
                    $nextDate, $unit, $twelveHourTime
                )
            ] = 0;

            if ($unit === self::BY_MONTHS) {
                $nextDate->addMonthWithOverflow();
            } elseif ($unit === self::BY_WEEKS) {
                $nextDate->addWeek();
            } elseif ($unit === self::BY_DAYS) {
                $nextDate->addDay();
            } elseif ($unit === self::BY_HOURS) {
                $nextDate->addHour();
            } elseif ($unit === self::BY_MINUTES) {
                $nextDate->addMinute();
            }
        } while ($nextDate->lte($endingDate));

        if (\count($possibleDateResults) < $possibleDateRange) {
            $possibleDateResults[
                $this->formatPossibleAggregateResultDate(
                    $nextDate, $unit, $twelveHourTime
                )
            ] = 0;
        }

        return $possibleDateResults;
    }

    /**
     * Format the possible aggregate result date into a proper string.
     */
    protected function formatPossibleAggregateResultDate(CarbonInterface $date, string $unit, bool $twelveHourTime): string
    {
        switch ($unit) {
            case 'month':
                return Nova::__($date->format('F')).' '.$date->format('Y');

            case 'week':
                return Nova::__($date->startOfWeek()->format('F')).' '.$date->startOfWeek()->format('j').' - '.
                       Nova::__($date->endOfWeek()->format('F')).' '.$date->endOfWeek()->format('j');

            case 'day':
                return Nova::__($date->format('F')).' '.$date->format('j').', '.$date->format('Y');

            case 'hour':
                return $twelveHourTime
                        ? Nova::__($date->format('F')).' '.$date->format('j').' - '.$date->format('g:00 A')
                        : Nova::__($date->format('F')).' '.$date->format('j').' - '.$date->format('G:00');

            case 'minute':
            default:
                return $twelveHourTime
                        ? Nova::__($date->format('F')).' '.$date->format('j').' - '.$date->format('g:i A')
                        : Nova::__($date->format('F')).' '.$date->format('j').' - '.$date->format('G:i');
        }
    }

    /**
     * Get default timezone.
     */
    private function getDefaultTimezone(Request $request): string
    {
        return $request->timezone ?? config('app.timezone', 'UTC');
    }
}
