<?php

namespace Laravel\Nova\Fields\Filters;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

class StatusFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    /**
     * Get the filter's available options.
     *
     * @return array
     */
    public function options(NovaRequest $request)
    {
        return [
            'loading' => Nova::__('Loading'),
            'finished' => Nova::__('Finished'),
            'failed' => Nova::__('Failed'),
        ];
    }

    /**
     * Prepare the filter for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'component' => $this->component,
            'field' => $this->serializeField(),
        ]);
    }
}
