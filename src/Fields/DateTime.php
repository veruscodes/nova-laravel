<?php

namespace Laravel\Nova\Fields;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Arr;
use Laravel\Nova\Contracts\FilterableField;
use Laravel\Nova\Fields\Filters\DateTimeFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

class DateTime extends Field implements FilterableField
{
    use FieldFilterable;
    use SupportsDependentFields;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'date-time-field';

    /**
     * The original raw value of the field.
     *
     * @var string|null
     */
    public $originalValue = null;

    /**
     * The minimum value that can be assigned to the field.
     *
     * @var string|null
     */
    public $min = null;

    /**
     * The maximum value that can be assigned to the field.
     *
     * @var string|null
     */
    public $max = null;

    /**
     * The step size the field will increment and decrement by.
     *
     * @var int|null
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
        parent::__construct($name, $attribute, $resolveCallback ?? static function ($value, $request) {
            if (! \is_null($value)) {
                if ($value instanceof DateTimeInterface) {
                    return $value instanceof CarbonInterface
                        ? $value->toIso8601String()
                        : $value->format(DateTimeInterface::ATOM);
                }

                throw new Exception("DateTime field must cast to 'datetime' in Eloquent model.");
            }
        });
    }

    /**
     * The minimum value that can be assigned to the field.
     *
     * @return $this
     */
    public function min(CarbonInterface|string $min)
    {
        if (\is_string($min)) {
            $min = Carbon::parse($min);
        }

        $this->min = $min->toDateTimeLocalString();

        return $this;
    }

    /**
     * The maximum value that can be assigned to the field.
     *
     * @return $this
     */
    public function max(CarbonInterface|string $max)
    {
        if (\is_string($max)) {
            $max = Carbon::parse($max);
        }

        $this->max = $max->toDateTimeLocalString();

        return $this;
    }

    /**
     * The step size the field will increment and decrement by.
     *
     * @return $this
     */
    public function step(CarbonInterval|int $step)
    {
        $this->step = $step instanceof CarbonInterval ? $step->totalSeconds : $step;

        return $this;
    }

    /**
     * Resolve the default value for the field.
     *
     * @return \Laravel\Nova\Support\UndefinedValue|string|null
     */
    #[\Override]
    public function resolveDefaultValue(NovaRequest $request): mixed
    {
        /** @var \Laravel\Nova\Support\UndefinedValue|\DateTimeInterface|string|null $value */
        $value = parent::resolveDefaultValue($request);

        if ($value instanceof DateTimeInterface) {
            return $value instanceof CarbonInterface
                ? $value->toIso8601String()
                : $value->format(DateTimeInterface::ATOM);
        }

        return $value;
    }

    /**
     * Resolve the field's value using the display callback.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object  $resource
     */
    #[\Override]
    protected function resolveUsingDisplayCallback(mixed $value, $resource, string $attribute): void
    {
        $this->usesCustomizedDisplay = true;

        if ($value instanceof DateTimeInterface) {
            $this->value = $value instanceof CarbonInterface
                ? $value->toIso8601String()
                : $value->format(DateTimeInterface::ATOM);
        }

        $this->originalValue = $this->value;
        $this->displayedAs = \call_user_func($this->displayCallback, $value, $resource, $attribute);
    }

    /**
     * Make the field filter.
     *
     * @return \Laravel\Nova\Fields\Filters\Filter
     */
    protected function makeFilter(NovaRequest $request)
    {
        return new DateTimeFilter($this);
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
            'placeholder',
            'extraAttributes',
        ]));
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge([
            'originalValue' => $this->originalValue,
        ], array_filter([
            'min' => $this->min,
            'max' => $this->max,
            'step' => $this->step ?? 1,
        ]), parent::jsonSerialize());
    }
}
