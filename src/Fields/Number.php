<?php

namespace Laravel\Nova\Fields;

use Illuminate\Support\Arr;
use Laravel\Nova\Fields\Filters\NumberFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

class Number extends Text
{
    /**
     * The minimum value that can be assigned to the field.
     *
     * @var int|float|string|null
     */
    public $min = null;

    /**
     * The maximum value that can be assigned to the field.
     *
     * @var int|float|string|null
     */
    public $max = null;

    /**
     * The step size the field will increment and decrement by.
     *
     * @var int|float|string|null
     */
    public $step = null;

    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     * @param  string|callable|object|null  $attribute
     * @param  (callable(mixed, mixed, ?string):(mixed))|null  $resolveCallback
     */
    public function __construct($name, mixed $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->textAlign(Field::RIGHT_ALIGN)
            ->withMeta(['type' => 'number'])
            ->displayUsing(function ($value) {
                return ! $this->isValidNullValue($value) ? (string) $value : null;
            });
    }

    /**
     * The minimum value that can be assigned to the field.
     *
     * @return $this
     */
    public function min(int|float|string|null $min)
    {
        $this->min = $min;

        return $this;
    }

    /**
     * The maximum value that can be assigned to the field.
     *
     * @return $this
     */
    public function max(int|float|string|null $max)
    {
        $this->max = $max;

        return $this;
    }

    /**
     * The step size the field will increment and decrement by.
     *
     * @return $this
     */
    public function step(int|float|string|null $step)
    {
        $this->step = $step;

        return $this;
    }

    /**
     * Make the field filter.
     *
     * @return \Laravel\Nova\Fields\Filters\Filter
     */
    protected function makeFilter(NovaRequest $request)
    {
        return new NumberFilter($this);
    }

    /**
     * Define the default filterable callback.
     *
     * @return callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Contracts\Database\Eloquent\Builder, mixed, string):\Illuminate\Contracts\Database\Eloquent\Builder
     */
    protected function defaultFilterableCallback()
    {
        return function (NovaRequest $request, $query, $value, $attribute) {
            [$min, $max] = $value;

            if (! \is_null($min) && ! \is_null($max)) {
                return $query->whereBetween($attribute, [$min, $max]);
            } elseif (! \is_null($min)) {
                return $query->where($attribute, '>=', $min);
            }

            return $query->where($attribute, '<=', $max);
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
            'type',
            'min',
            'max',
            'step',
            'pattern',
            'placeholder',
            'extraAttributes',
        ]));
    }

    /**
     * Prepare the element for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), collect([
            'min' => $this->min,
            'max' => $this->max,
            'step' => $this->step,
        ])->reject(static fn ($value) => \is_null($value) || (empty($value) && $value !== 0))
        ->all());
    }
}
