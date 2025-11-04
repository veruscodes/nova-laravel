<?php

namespace Laravel\Nova\Fields;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Arr;
use Laravel\Nova\Contracts\FilterableField;
use Laravel\Nova\Fields\Filters\DateFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

class Date extends Field implements FilterableField
{
    use FieldFilterable;
    use SupportsDependentFields;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'date-field';

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
     * @var string|int|null
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
        parent::__construct($name, $attribute, $resolveCallback ?? static function ($value) {
            if (! \is_null($value)) {
                if ($value instanceof DateTimeInterface) {
                    return $value instanceof CarbonInterface
                        ? $value->toDateString()
                        : $value->format('Y-m-d');
                }

                throw new Exception("Date field must cast to 'date' in Eloquent model.");
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

        $this->min = $min->toDateString();

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

        $this->max = $max->toDateString();

        return $this;
    }

    /**
     * The step size the field will increment and decrement by.
     *
     * @return $this
     */
    public function step(CarbonInterval|string|int $step)
    {
        $this->step = $step instanceof CarbonInterval ? $step->totalDays : $step;

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
                ? $value->toDateString()
                : $value->format('Y-m-d');
        }

        return $value;
    }

    /**
     * Make the field filter.
     *
     * @return \Laravel\Nova\Fields\Filters\Filter
     */
    protected function makeFilter(NovaRequest $request)
    {
        return new DateFilter($this);
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
     * Prepare the element for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), array_filter([
            'min' => $this->min,
            'max' => $this->max,
            'step' => $this->step ?? 'any',
        ]));
    }
}
