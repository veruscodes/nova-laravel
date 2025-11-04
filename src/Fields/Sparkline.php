<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;

use function Orchestra\Sidekick\is_safe_callable;

class Sparkline extends Field implements Unfillable
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'sparkline-field';

    /**
     * The data used in the chart.
     *
     * @var array|(callable(\Laravel\Nova\Http\Requests\NovaRequest):(mixed))|\Laravel\Nova\Metrics\Trend
     */
    public $data = [];

    /**
     * The type of chart to use.
     *
     * @var string
     */
    public $chartStyle = 'Line';

    /**
     * Indicates if the element should be shown on the creation view.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool
     */
    public $showOnCreation = false;

    /**
     * Indicates if the element should be shown on the update view.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest, mixed):(bool))|bool
     */
    public $showOnUpdate = false;

    /**
     * Set the data for the Spark Line.
     *
     * @param  \Laravel\Nova\Metrics\Trend|(callable(\Laravel\Nova\Http\Requests\NovaRequest):(mixed))|iterable  $data
     * @return $this
     */
    public function data(Trend|callable|iterable $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get field data.
     *
     * @return mixed
     */
    public function getData(NovaRequest $request)
    {
        if ($this->data instanceof Trend) {
            $ranges = $this->data->ranges();
            $defaultRange = array_key_first($ranges);

            return array_values(
                $this->data->calculate(
                    $request->merge([
                        'range' => $defaultRange,
                        'resourceId' => $this->data->component,
                    ])
                )->trend ?? []
            );
        } elseif (is_safe_callable($this->data)) {
            return \call_user_func($this->data, $request);
        }

        return $this->data;
    }

    /**
     * Format the sparkline as a bar.
     *
     * @return $this
     */
    public function asBarChart()
    {
        $this->chartStyle = 'Bar';

        return $this;
    }

    /**
     * Set the component height.
     *
     * @return $this
     */
    public function height(int $height)
    {
        return $this->withMeta([
            __FUNCTION__ => $height,
        ]);
    }

    /**
     * Set the component width.
     *
     * @return $this
     */
    public function width(int $width)
    {
        return $this->withMeta([
            __FUNCTION__ => $width,
        ]);
    }

    /**
     * Prepare the element for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'chartStyle' => $this->chartStyle,
            'data' => $this->getData(app(NovaRequest::class)),
        ]);
    }
}
