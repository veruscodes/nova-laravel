<?php

namespace Laravel\Nova\Fields;

use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;

use function Orchestra\Sidekick\Eloquent\model_exists;
use function Orchestra\Sidekick\Http\safe_int;

/**
 * @method static static make(\Stringable|string|null $name = null, string|null $attribute = null, callable|null $resolveCallback = null)
 */
class ID extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'id-field';

    /**
     * The field's resolved pivot value.
     *
     * @var string|int|null
     */
    public $pivotValue = null;

    /**
     * Create a new field.
     *
     * @param  \Stringable|string|null  $name
     * @param  (callable(mixed, mixed, ?string):(mixed))|null  $resolveCallback
     */
    public function __construct($name = null, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        if (\is_null($name)) {
            $attribute ??= 'id';
            $name = Nova::__('ID');
        }

        parent::__construct($name, $attribute, $resolveCallback);
    }

    /**
     * Create a new hidden ID field.
     *
     * @param  \Stringable|string  $name
     */
    public static function hidden($name = 'ID', string $attribute = 'id', ?callable $resolveCallback = null): Hidden
    {
        return Hidden::make($name, $attribute, $resolveCallback);
    }

    /**
     * Create a new, resolved ID field for the given resource.
     */
    public static function forResource(Resource $resource): ?static
    {
        $model = $resource->model();

        /** @var static|null $field */
        /** @phpstan-ignore argument.templateType */
        $field = transform(
            $resource->availableFieldsOnIndexOrDetail(app(NovaRequest::class))
                    ->whereInstanceOf(self::class)
                    ->first(),
            static fn ($field) => tap($field)->resolve($model),
            static fn () => model_exists($model) ? static::forModel($model) : null,
        );

        if ($field instanceof static) {
            return empty($field->value) && $field->nullable !== true ? null : $field;
        }

        return null;
    }

    /**
     * Create a new, resolved ID field for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public static function forModel($model): static
    {
        return tap(static::make('ID', $model->getKeyName()), static function ($field) use ($model) {
            $value = $model->getKey();

            if (\is_int($value) && $value >= 9007199254740991) {
                $field->asBigInt();
            }

            $field->resolve($model);
        });
    }

    /**
     * Resolve the given attribute from the given resource.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object  $resource
     */
    #[\Override]
    protected function resolveAttribute($resource, string $attribute): string|int|null
    {
        if ($resource instanceof Model) {
            $pivotAccessor = $this->pivotAccessor ?? 'pivot';

            $pivotValue = $resource->relationLoaded($pivotAccessor)
                ? optional($resource->{$pivotAccessor})->getKey()
                : null;

            if (\is_int($pivotValue) || \is_string($pivotValue)) {
                $this->pivotValue = $pivotValue;
            }
        }

        return safe_int(
            parent::resolveAttribute($resource, $attribute)
        );
    }

    /**
     * Resolve a BIGINT ID field as a string for compatibility with JavaScript.
     *
     * @return $this
     */
    public function asBigInt()
    {
        $this->resolveCallback = static fn ($id) => (string) $id;

        return $this;
    }

    /**
     * Hide the ID field from the Nova interface but keep it available for operations.
     *
     * @return $this
     */
    public function hide()
    {
        $this->showOnIndex = false;
        $this->showOnDetail = false;
        $this->showOnCreation = false;
        $this->showOnUpdate = false;

        return $this;
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), array_filter([
            'pivotValue' => $this->pivotValue ?? null,
        ]));
    }
}
