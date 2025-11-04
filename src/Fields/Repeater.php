<?php

namespace Laravel\Nova\Fields;

use Illuminate\Support\Arr;
use Laravel\Nova\Exceptions\NovaException;
use Laravel\Nova\Fields\Repeater\Presets\HasMany;
use Laravel\Nova\Fields\Repeater\Presets\JSON;
use Laravel\Nova\Fields\Repeater\Presets\Preset;
use Laravel\Nova\Fields\Repeater\Repeatable;
use Laravel\Nova\Fields\Repeater\RepeatableCollection;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * @phpstan-import-type TFieldValidationRules from \Laravel\Nova\Fields\Field
 */
class Repeater extends Field
{
    /**
     * The resource class for the repeater.
     *
     * @var class-string<\Laravel\Nova\Resource>|null
     */
    public $resourceClass;

    /**
     * The resource name for the repeater.
     *
     * @var string|null
     */
    public $resourceName;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'repeater-field';

    /**
     * Indicates if the field label and form element should sit on top of each other.
     *
     * @var bool
     */
    public $stacked = false;

    /**
     * Indicates if the field should be sortable.
     *
     * @var bool
     */
    public $sortable = true;

    /**
     * Indicates whether the field should use all available white-space.
     */
    public $fullWidth = false;

    /**
     * The repeatable types used for the Repeater.
     */
    public RepeatableCollection $repeatables;

    /**
     * The repeatable type unique field.
     *
     * @var string|null
     */
    public $uniqueField = null;

    /**
     * The preset used for the field.
     *
     * @var \Laravel\Nova\Fields\Repeater\Presets\Preset|null
     */
    public $preset = null;

    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     * @param  (callable(mixed, mixed, ?string):mixed)|null  $resolveCallback
     */
    public function __construct($name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->onlyOnForms();
        $this->repeatables = RepeatableCollection::make();
    }

    /**
     * Specify the callback to be executed to retrieve the pivot fields.
     *
     * @param  array<int, \Laravel\Nova\Fields\Repeater\Repeatable>  $repeatables
     * @return $this
     */
    public function repeatables(array $repeatables)
    {
        foreach ($repeatables as $repeatable) {
            $this->repeatables->push($repeatable);
        }

        return $this;
    }

    /**
     * Set the preset used for the field.
     *
     * @return $this
     */
    public function preset(Preset $preset)
    {
        $this->preset = $preset;

        return $this;
    }

    /**
     * Use the JSON preset for the field.
     *
     * @return $this
     */
    public function asJson()
    {
        return $this->preset(new JSON)
            ->onlyOnForms()
            ->showOnDetail();
    }

    /**
     * Use the HasMany preset for the field.
     *
     * @param  class-string<\Laravel\Nova\Resource>|null  $resourceClass
     * @return $this
     *
     * @throws \Laravel\Nova\Exceptions\NovaException
     */
    public function asHasMany($resourceClass = null)
    {
        /** @var class-string<\Laravel\Nova\Resource>|null $resourceClass */
        $resourceClass ??= ResourceRelationshipGuesser::guessResource($this->name);

        if ($resourceClass) {
            $this->resourceClass = $resourceClass;
            $this->resourceName = $resourceClass::uriKey();

            return $this->preset(new HasMany)->onlyOnForms();
        }

        throw NovaException::missingResourceForRepeater($this->name);
    }

    /**
     * Return the preset instance for the field.
     *
     * @return \Laravel\Nova\Fields\Repeater\Presets\Preset
     */
    public function getPreset()
    {
        return $this->preset ?? new JSON;
    }

    /**
     * Resolve the given attribute from the given resource.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|object|array  $resource
     */
    protected function resolveAttribute($resource, string $attribute): mixed
    {
        $request = app(NovaRequest::class);

        return $this->getPreset()->get($request, $resource, $attribute, $this->repeatables);
    }

    /**
     * Determine if the field collection contains an ID field.
     */
    protected function fieldsContainsIDField(FieldCollection $fields): bool
    {
        return $fields->contains(function (Field $field) {
            return $field instanceof ID && $field->attribute === $this->uniqueField
                || $field instanceof Hidden && $field->attribute === $this->uniqueField;
        });
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     */
    protected function fillAttributeFromRequest(NovaRequest $request, string $requestAttribute, object $model, string $attribute): callable
    {
        return $this->getPreset()->set($request, $requestAttribute, $model, $attribute, $this->repeatables, $this->uniqueField);
    }

    /**
     * Get the creation rules for this field.
     *
     * @return array<array-key, mixed>
     *
     * @phpstan-return array<string, TFieldValidationRules>
     */
    public function getCreationRules(NovaRequest $request): array
    {
        return array_merge_recursive(parent::getCreationRules($request), $this->formatRules());
    }

    /**
     * Get the update rules for this field.
     *
     * @return array<array-key, mixed>
     *
     * @phpstan-return array<string, TFieldValidationRules>
     */
    public function getUpdateRules(NovaRequest $request): array
    {
        return array_merge_recursive(parent::getUpdateRules($request), $this->formatRules());
    }

    /**
     * Format available rules.
     *
     * @return array<array-key, mixed>
     *
     * @phpstan-return array<string, TFieldValidationRules>
     */
    protected function formatRules(): array
    {
        $request = app(NovaRequest::class);

        if ($request->method() === 'GET') {
            return [];
        }

        $validationKey = $this->validationKey();

        return collect($request->{$this->validationKey()})
            ->map(fn ($item) => $this->repeatables->findByKey($item['type']))
            ->flatMap(function (Repeatable $repeatable, $index) use ($request, $validationKey) {
                $key = "{$validationKey}.{$index}.fields";

                return FieldCollection::make($repeatable->fields($request))
                    ->mapWithKeys(fn (Field $field) => [
                        "{$key}.{$field->attribute}" => $this->replaceRulesPlaceholder($field->rules, $key),
                    ]);
            })
            ->all();
    }

    /**
     * Get the validation attribute names for the field.
     *
     * @return array<string, string>
     */
    public function getValidationAttributeNames(NovaRequest $request): array
    {
        if ($request->method() === 'GET') {
            return [];
        }

        $validationKey = $this->validationKey();

        return collect($request->{$this->validationKey()})
            ->map(fn ($item) => $this->repeatables->findByKey($item['type']))
            ->flatMap(function (Repeatable $repeatable, $index) use ($request, $validationKey) {
                return FieldCollection::make($repeatable->fields($request))
                    ->mapWithKeys(static fn (Field $field) => [
                        "{$validationKey}.{$index}.fields.{$field->attribute}" => $field->name,
                    ]);
            })->all();
    }

    /**
     * Replaces all rules any final formatting of the given validation rules.
     *
     *
     * @return array<array-key, mixed>
     *
     * @phpstan-return array<array-key, TFieldValidationRules>
     */
    protected function replaceRulesPlaceholder(array $rules, string $replaceWith = ''): array
    {
        $replacements = array_filter([
            '{{repeatable}}' => str_replace(['\'', '"', ',', '\\'], '', $replaceWith),
        ]);

        if (empty($replacements)) {
            return $rules;
        }

        return collect($rules)->map(static function ($fieldRules) use ($replacements) {
            return collect(Arr::wrap($fieldRules))->map(static function ($rule) use ($replacements) {
                return \is_string($rule)
                    ? str_replace(array_keys($replacements), array_values($replacements), $rule)
                    : $rule;
            })->all();
        })->flatten()->all();
    }

    /**
     * Set the unique database column to use when attempting upserts.
     *
     * @return $this
     */
    public function uniqueField(?string $key)
    {
        $this->uniqueField = $key;

        return $this;
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_merge([
            'repeatables' => $this->repeatables,
            'sortable' => $this->sortable,
        ], parent::jsonSerialize());
    }
}
