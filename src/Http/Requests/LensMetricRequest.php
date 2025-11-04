<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Support\Collection;
use Laravel\Nova\Metrics\Metric;

class LensMetricRequest extends MetricRequest
{
    use InteractsWithLenses;

    /**
     * Get all of the possible metrics for the request.
     */
    public function availableMetrics(): Collection
    {
        return $this->lens()
            ->availableCards($this)
            ->whereInstanceOf(Metric::class);
    }
}
