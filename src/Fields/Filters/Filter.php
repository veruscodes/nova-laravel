<?php

namespace Laravel\Nova\Fields\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Contracts\FilterableField;
use Laravel\Nova\Filters\Filter as BaseFilter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Makeable;

/**
 * @method static static make(\Laravel\Nova\Contracts\FilterableField&\Laravel\Nova\Fields\Field $field)
 */
abstract class Filter extends BaseFilter
{
    use Makeable;

    /**
     * Construct a new filter.
     *
     * @param  \Laravel\Nova\Contracts\FilterableField&\Laravel\Nova\Fields\Field  $field
     */
    public function __construct(public FilterableField $field)
    {
        //
    }

    /**
     * Get the displayable name of the filter.
     *
     * @return string
     */
    public function name()
    {
        return $this->field->name;
    }

    /**
     * Get the key for the filter.
     *
     * @return string
     */
    public function key()
    {
        return class_basename($this->field).':'.$this->field->attribute;
    }

    /**
     * Apply the filter to the given query.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, Builder $query, mixed $value)
    {
        $this->field->applyFilter($request, $query, $value);

        return $query;
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array
     */
    public function serializeField()
    {
        return $this->field->serializeForFilter();
    }

    /**
     * Prepare the filter for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        $component = $this->component();

        return array_merge(parent::jsonSerialize(), [
            'uniqueKey' => \sprintf('%s-%s-filter', $this->field->attribute, $component),
            'component' => "filter-{$component}",
            'field' => $this->serializeField(),
        ]);
    }
}
