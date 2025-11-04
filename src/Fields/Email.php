<?php

namespace Laravel\Nova\Fields;

use Illuminate\Support\Arr;
use Laravel\Nova\Contracts\FilterableField;
use Laravel\Nova\Fields\Filters\TextFilter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

class Email extends Text implements FilterableField
{
    use FieldFilterable;
    use SupportsDependentFields;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'email-field';

    /**
     * Create a new field.
     *
     * @param  \Stringable|string|null  $name
     * @param  string|callable|object|null  $attribute
     * @param  (callable(mixed, mixed, ?string):(mixed))|null  $resolveCallback
     */
    public function __construct($name = null, mixed $attribute = 'email', ?callable $resolveCallback = null)
    {
        if (\is_null($name)) {
            $attribute ??= 'email';
        }

        parent::__construct($name ?? Nova::__('Email'), $attribute, $resolveCallback);

        $this->withAutoCompletion('email');
    }

    /**
     * Make the field filter.
     *
     * @return \Laravel\Nova\Fields\Filters\Filter
     */
    protected function makeFilter(NovaRequest $request)
    {
        return tap(new TextFilter($this), static function ($filter) {
            $filter->component = 'email-field';
        });
    }

    /**
     * Prepare the field for JSON serialization.
     */
    public function serializeForFilter(): array
    {
        return transform($this->jsonSerialize(), static fn ($field) => Arr::only($field, [
            'uniqueKey',
            'name',
            'attribute',
            'type',
            'min',
            'max',
            'step',
            'pattern',
            'placeholder',
            'extraAttributes',
        ]));
    }
}
