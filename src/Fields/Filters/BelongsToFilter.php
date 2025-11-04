<?php

namespace Laravel\Nova\Fields\Filters;

use Laravel\Nova\Contracts\FilterableField;

/**
 * @method static static make(\Laravel\Nova\Contracts\FilterableField&\Laravel\Nova\Fields\Field $field, string $resourceName)
 */
class BelongsToFilter extends EloquentFilter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'belongs-to-field';

    /**
     * Construct a new filter.
     *
     * @param  \Laravel\Nova\Contracts\FilterableField&\Laravel\Nova\Fields\Field  $field
     * @param  class-string<\Laravel\Nova\Resource>  $resourceName
     */
    public function __construct(
        FilterableField $field,
        public string $resourceName
    ) {
        parent::__construct($field);
    }

    /**
     * Prepare the filter for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'resourceName' => $this->resourceName,
        ]);
    }
}
