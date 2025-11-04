<?php

namespace Laravel\Nova\Fields;

use Closure;
use Illuminate\Support\Arr;
use Laravel\Nova\Contracts\FilterableField;
use Laravel\Nova\Fields\Filters\BooleanGroupFilter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Stringable;

use function Orchestra\Sidekick\is_safe_callable;

/**
 * @phpstan-type TOptionLabel \Stringable|string
 * @phpstan-type TOptionValue string
 * @phpstan-type TOption iterable<TOptionValue|int, TOptionLabel>
 */
class BooleanGroup extends Field implements FilterableField
{
    use FieldFilterable;
    use SupportsDependentFields;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'boolean-group-field';

    /**
     * The text alignment for the field's text in tables.
     *
     * @var string
     */
    public $textAlign = 'center';

    /**
     * The text to be used when there are no booleans to show.
     *
     * @var \Stringable|string
     */
    public $noValueText = 'No Data';

    /**
     * The field's options callback.
     *
     * @var iterable<string|int, string>|callable|null
     *
     * @phpstan-var TOption|(callable(): (TOption))|null
     */
    public $optionsCallback;

    /**
     * Determine false values should be hidden.
     *
     * @var bool|null
     */
    public $hideFalseValues = null;

    /**
     * Determine true values should be hidden.
     *
     * @var bool|null
     */
    public $hideTrueValues = null;

    /**
     * Set the options for the field.
     *
     * @param  callable():(iterable)|iterable  $options
     * @return $this
     */
    public function options(callable|iterable $options)
    {
        $this->optionsCallback = $options;

        return $this;
    }

    /**
     * Serialize options for the field.
     *
     * @return array<int, array<string, mixed>>
     *
     * @phpstan-return array<int, array{label: string, value: string}>
     */
    protected function serializeOptions(): array
    {
        /** @var TOption $options */
        $options = ! is_safe_callable($this->optionsCallback) && ! $this->optionsCallback instanceof Closure
            ? value($this->optionsCallback)
            : \call_user_func($this->optionsCallback);

        return with(collect($options), static function ($options) {
            $isList = array_is_list($options->all());

            return $options->map(static function ($label, $name) use ($isList) {
                return $isList === false
                    ? ['label' => $label, 'name' => $name]
                    : ['label' => $label, 'name' => $label];
            })->values()->all();
        });
    }

    /**
     * Whether false values should be hidden on the index.
     *
     * @return $this
     */
    public function hideFalseValues()
    {
        $this->hideTrueValues = false;
        $this->hideFalseValues = true;

        return $this;
    }

    /**
     * Whether true values should be hidden on the index.
     *
     * @return $this
     */
    public function hideTrueValues()
    {
        $this->hideTrueValues = true;
        $this->hideFalseValues = false;

        return $this;
    }

    /**
     * Set the text to be used when there are no booleans to show.
     *
     * @return $this
     */
    public function noValueText(Stringable|string $text)
    {
        $this->noValueText = $text;

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
            $model->{$attribute} = json_decode($request[$requestAttribute], true);
        }
    }

    /**
     * Make the field filter.
     *
     * @return \Laravel\Nova\Fields\Filters\Filter
     */
    protected function makeFilter(NovaRequest $request)
    {
        return new BooleanGroupFilter($this);
    }

    /**
     * Define the default filterable callback.
     *
     * @return callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Contracts\Database\Eloquent\Builder, mixed, string):void
     */
    protected function defaultFilterableCallback()
    {
        return function (NovaRequest $request, $query, $value, $attribute) {
            $value = collect($value)
                ->reject(static fn ($value) => \is_null($value))
                ->all();

            $query->when(! empty($value), static function ($query) use ($value, $attribute) {
                return $query->whereJsonContains($attribute, $value);
            });
        };
    }

    /**
     * Prepare the field for JSON serialization.
     */
    public function serializeForFilter(): array
    {
        return transform($this->jsonSerialize(), static function ($field) {
            $field['options'] = collect($field['options'])
                ->transform(static fn ($option) => [
                    'label' => $option['label'],
                    'value' => $option['name'],
                ]);

            return Arr::only($field, ['uniqueKey', 'options']);
        });
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'hideTrueValues' => $this->hideTrueValues,
            'hideFalseValues' => $this->hideFalseValues,
            'options' => $this->serializeOptions(),
            'noValueText' => Nova::__($this->noValueText),
        ]);
    }
}
