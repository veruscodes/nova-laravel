<?php

namespace Laravel\Nova\Fields;

use Illuminate\Support\Arr;
use Laravel\Nova\Contracts\FilterableField;
use Laravel\Nova\Fields\Filters\MultiSelectFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

use function Orchestra\Sidekick\Http\safe_int;

/**
 * @phpstan-type TOptionLabel \Stringable|string|array{label: string, group?: string}
 * @phpstan-type TOptionValue string|int
 * @phpstan-type TOption iterable<TOptionValue, TOptionLabel>
 */
class MultiSelect extends Field implements FilterableField
{
    use FieldFilterable;
    use SupportsDependentFields;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'multi-select-field';

    /**
     * The field's options callback.
     *
     * @var iterable<string|int, array<string, mixed>|string>|callable|null
     *
     * @phpstan-var TOption|(callable(): (TOption))|null
     */
    public $optionsCallback;

    /**
     * Set display using label for the field.
     *
     * @var bool
     */
    public $displayUsingLabel = false;

    /**
     * Set the options for the select menu.
     *
     * @param  iterable<string|int, array<string, mixed>|string>|callable  $options
     *
     * @phpstan-param TOption|(callable(): (TOption)) $options
     *
     * @return $this
     */
    public function options(iterable|callable $options)
    {
        $this->optionsCallback = $options;

        return $this;
    }

    /**
     * Display values using their corresponding specified labels.
     *
     * @return $this
     */
    public function displayUsingLabels()
    {
        $this->displayUsingLabel = true;

        return $this;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     */
    #[\Override]
    protected function fillAttributeFromRequest(NovaRequest $request, string $requestAttribute, object $model, string $attribute): void
    {
        if ($request->exists($requestAttribute)) {
            $value = $request[$requestAttribute];

            $model->{$attribute} = $this->isValidNullValue($value) ? null : json_decode($value, true);
        }
    }

    /**
     * Make the field filter.
     *
     * @return \Laravel\Nova\Fields\Filters\Filter
     */
    protected function makeFilter(NovaRequest $request)
    {
        return new MultiSelectFilter($this);
    }

    /**
     * Define the default filterable callback.
     *
     * @return callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Contracts\Database\Eloquent\Builder, mixed, string):\Illuminate\Contracts\Database\Eloquent\Builder
     */
    protected function defaultFilterableCallback()
    {
        return function (NovaRequest $request, $query, $value, $attribute) {
            return $query->whereJsonContains($attribute, $value);
        };
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
            'options',
        ]));
    }

    /**
     * Serialize options for the field.
     *
     * @return array<int, array<string, mixed>>
     *
     * @phpstan-return array<int, array{group: string, label: string, value: TOptionValue}>
     */
    protected function serializeOptions()
    {
        /** @var TOption $options */
        $options = value($this->optionsCallback);

        if (\is_callable($options)) {
            $options = $options();
        }

        return collect($options ?? [])->map(static function ($label, $value) {
            $value = safe_int($value);

            return \is_array($label) ? $label + ['value' => $value] : ['label' => $label, 'value' => $value];
        })->values()->all();
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        $this->withMeta([
            'options' => $options = $this->serializeOptions(),
        ]);

        if ($this->displayUsingLabel === true) {
            $this->displayUsing(static function ($value) use ($options) {
                return collect($options)
                    ->where('value', $value)
                    ->first()['label'] ?? $value;
            });
        }

        return parent::jsonSerialize();
    }
}
