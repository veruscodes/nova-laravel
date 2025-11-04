<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Http\Requests\NovaRequest;

trait FieldFilterable
{
    use Filterable;

    /**
     * Prepare the field for JSON serialization.
     */
    public function serializeForFilter(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * Define filterable attribute.
     *
     * @return string
     */
    protected function filterableAttribute(NovaRequest $request)
    {
        return $this->attribute;
    }
}
