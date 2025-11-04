<?php

namespace Laravel\Nova\Metrics;

use Carbon\CarbonInterface;
use Closure;
use DateInterval;
use DateTimeImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Nova\Card;
use Laravel\Nova\Exceptions\HelperNotSupported;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

abstract class Metric extends Card
{
    use HasHelpText;
    use ResolvesFilters;

    /**
     * The displayable name of the metric.
     *
     * @var \Stringable|string
     */
    public $name;

    /**
     * Indicates whether the metric should be refreshed when actions run.
     *
     * @var bool
     */
    public $refreshWhenActionRuns = false;

    /**
     * Indicates whether the metric should be refreshed when a filter is changed.
     *
     * @var bool
     */
    public $refreshWhenFiltersChange = false;

    /**
     * Calculate the metric's value.
     *
     * @return mixed
     */
    public function resolve(NovaRequest $request)
    {
        $resolver = $this->getResolver($request);

        if ($cacheFor = $this->cacheFor()) {
            $cacheFor = is_numeric($cacheFor) ? new DateInterval(\sprintf('PT%dS', $cacheFor * 60)) : $cacheFor;

            return Cache::remember(
                $this->getCacheKey($request),
                $cacheFor,
                $resolver
            );
        }

        return value($resolver);
    }

    /**
     * Return a resolver function for the metric.
     *
     * @return \Closure(): mixed
     */
    public function getResolver(NovaRequest $request): Closure
    {
        return function () use ($request) {
            return $this->onlyOnDetail
                ? $this->calculate($request, $request->findModelOrFail())
                : $this->calculate($request);
        };
    }

    /**
     * Get the appropriate cache key for the metric.
     *
     * @return string
     */
    public function getCacheKey(NovaRequest $request)
    {
        return \sprintf(
            'nova.metric.%s.%s.%s.%s.%s.%s',
            $this->uriKey(),
            $request->input('range', 'no-range'),
            $request->input('timezone', 'no-timezone'),
            $request->input('twelveHourTime', 'no-12-hour-time'),
            $this->onlyOnDetail ? $request->findModelOrFail()->getKey() : 'no-resource-id',
            md5($request->input('filter', 'no-filter'))
        );
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return \Stringable|string
     */
    public function name()
    {
        return $this->name ?: Nova::humanize($this);
    }

    /**
     * Determine for how many minutes the metric should be cached.
     *
     * @return \DateTimeInterface|\DateInterval|float|int|null
     */
    public function cacheFor()
    {
        return null;
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return Str::slug($this->name(), '-', null);
    }

    /**
     * Set whether the metric should refresh when actions are run.
     *
     * @return $this
     */
    public function refreshWhenActionsRun(bool $value = true)
    {
        $this->refreshWhenActionRuns = $value;

        return $this;
    }

    /**
     * Set whether the metric should refresh when actions are run.
     *
     * @return $this
     *
     * @deprecated 4.0.0 Use "refreshWhenActionsRun()"
     */
    #[\Deprecated('Use `refreshWhenActionsRun()` method instead', since: '4.0.0')]
    public function refreshWhenActionRuns(bool $value = true)
    {
        return $this->refreshWhenActionsRun($value);
    }

    /**
     * Set whether the metric should refresh when filter changed.
     *
     * @return $this
     *
     * @throws \Laravel\Nova\Exceptions\HelperNotSupported
     */
    public function refreshWhenFiltersChange(bool $value = true)
    {
        if ($this->onlyOnDetail === true && $value === true) {
            throw new HelperNotSupported(\sprintf('The %s helper method is not compatible with onlyOnDetail helper.', __METHOD__));
        }

        $this->refreshWhenFiltersChange = $value;

        return $this;
    }

    /**
     * Specify that the element should only be shown on the detail view.
     *
     * @return $this
     *
     * @throws \Laravel\Nova\Exceptions\HelperNotSupported
     */
    public function onlyOnDetail()
    {
        if ($this->refreshWhenFiltersChange === true) {
            throw new HelperNotSupported(\sprintf('The %s helper method is not compatible with refreshWhenFiltersChange helper.', __METHOD__));
        }

        $this->onlyOnDetail = true;

        return $this;
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
            'class' => $this::class,
            'name' => $this->name(),
            'uriKey' => $this->uriKey(),
            'helpWidth' => $this->getHelpWidth(),
            'helpText' => $this->getHelpText(),
            'refreshWhenActionRuns' => $this->refreshWhenActionRuns,
            'refreshWhenFiltersChange' => $this->refreshWhenFiltersChange,
        ]);
    }

    /**
     * Convert datetime to application timezone.
     */
    protected function asQueryDatetime(CarbonInterface $datetime): CarbonInterface
    {
        if (! $datetime instanceof DateTimeImmutable) {
            return $datetime->copy()->timezone(config('app.timezone'));
        }

        return $datetime->timezone(config('app.timezone'));
    }

    /**
     * Format date between.
     */
    protected function formatQueryDateBetween(array $ranges): array
    {
        return array_map(function ($datetime) {
            return $this->asQueryDatetime($datetime);
        }, $ranges);
    }
}
