<?php

namespace Laravel\Nova;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Laravel\Nova\Contracts\PivotableField;
use Laravel\Nova\Http\Requests\NovaRequest;
use Stringable;

/**
 * @phpstan-import-type TFieldValidationRules from \Laravel\Nova\Fields\Field
 */
trait PerformsValidation
{
    /**
     * Validate a resource creation request.
     *
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function validateForCreation(NovaRequest $request): void
    {
        /** @phpstan-ignore method.notFound */
        static::validatorForCreation($request)
            ->addCustomAttributes(self::attributeNamesForFields($request)->toArray())
            ->validate();
    }

    /**
     * Create a validator instance for a resource creation request.
     */
    public static function validatorForCreation(NovaRequest $request): ValidatorContract
    {
        return Validator::make($request->all(), static::rulesForCreation($request))
            ->after(static function ($validator) use ($request) {
                static::afterValidation($request, $validator);
                static::afterCreationValidation($request, $validator);
            });
    }

    /**
     * Get the validation rules for a resource creation request.
     *
     * @return array<array-key, mixed>
     *
     * @phpstan-return array<array-key, TFieldValidationRules>
     */
    public static function rulesForCreation(NovaRequest $request): array
    {
        return static::formatRules($request, self::newResource()
            ->creationFields($request)
            ->applyDependsOn($request)
            ->withoutReadonly($request)
            ->withoutUnfillable()
            ->mapWithKeys(static fn ($field) => $field->getCreationRules($request))
            ->all());
    }

    /**
     * Get the creation validation rules for a specific field.
     *
     * @return array<array-key, mixed>
     *
     * @phpstan-return array<array-key, TFieldValidationRules>
     */
    public static function creationRulesFor(NovaRequest $request, string $field): array
    {
        return static::formatRules($request, self::newResource()
            ->availableFields($request)
            ->where('attribute', $field)
            ->applyDependsOn($request)
            ->withoutUnfillable()
            ->mapWithKeys(static fn ($field) => $field->getCreationRules($request))
            ->all());
    }

    /**
     * Validate a resource update request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function validateForUpdate(NovaRequest $request, ?Resource $resource = null): void
    {
        /** @phpstan-ignore method.notFound */
        static::validatorForUpdate($request, $resource)
            ->addCustomAttributes(self::attributeNamesForFields($request, $resource)->toArray())
            ->validate();
    }

    /**
     * Create a validator instance for a resource update request.
     *
     * @param  \Laravel\Nova\Resource|null  $resource
     */
    public static function validatorForUpdate(NovaRequest $request, ?Resource $resource = null): ValidatorContract
    {
        return Validator::make($request->all(), static::rulesForUpdate($request, $resource))
            ->after(static function ($validator) use ($request) {
                static::afterValidation($request, $validator);
                static::afterUpdateValidation($request, $validator);
            });
    }

    /**
     * Get the validation rules for a resource update request.
     *
     * @param  \Laravel\Nova\Resource|null  $resource
     * @return array<array-key, mixed>
     *
     * @phpstan-return array<array-key, TFieldValidationRules>
     */
    public static function rulesForUpdate(NovaRequest $request, ?Resource $resource = null): array
    {
        $resource ??= self::newResource();

        return static::formatRules($request, $resource->updateFields($request)
            ->applyDependsOn($request)
            ->withoutReadonly($request)
            ->withoutUnfillable()
            ->mapWithKeys(static fn ($field) => $field->getUpdateRules($request))
            ->all());
    }

    /**
     * Get the update validation rules for a specific field.
     *
     * @return array<array-key, mixed>
     *
     * @phpstan-return array<array-key, TFieldValidationRules>
     */
    public static function updateRulesFor(NovaRequest $request, string $field): array
    {
        return static::formatRules($request, self::newResource()
            ->availableFields($request)
            ->where('attribute', $field)
            ->applyDependsOn($request)
            ->withoutUnfillable()
            ->mapWithKeys(static fn ($field) => $field->getUpdateRules($request))
            ->all());
    }

    /**
     * Validate a resource attachment request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function validateForAttachment(NovaRequest $request): void
    {
        static::validatorForAttachment($request)->validate();
    }

    /**
     * Create a validator instance for a resource attachment request.
     */
    public static function validatorForAttachment(NovaRequest $request): ValidatorContract
    {
        return Validator::make($request->all(), static::rulesForAttachment($request));
    }

    /**
     * Get the validation rules for a resource attachment request.
     *
     * @return array<array-key, mixed>
     *
     * @phpstan-return array<array-key, TFieldValidationRules>
     */
    public static function rulesForAttachment(NovaRequest $request): array
    {
        return static::formatRules($request, self::newResource()
            ->creationPivotFields($request, $request->relatedResource)
            ->mapWithKeys(static fn ($field) => $field->getCreationRules($request))
            ->all());
    }

    /**
     * Validate a resource attachment update request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function validateForAttachmentUpdate(NovaRequest $request): void
    {
        static::validatorForAttachmentUpdate($request)->validate();
    }

    /**
     * Create a validator instance for a resource attachment update request.
     */
    public static function validatorForAttachmentUpdate(NovaRequest $request): ValidatorContract
    {
        return Validator::make($request->all(), static::rulesForAttachmentUpdate($request));
    }

    /**
     * Get the validation rules for a resource attachment update request.
     *
     * @return array<array-key, mixed>
     *
     * @phpstan-return array<array-key, TFieldValidationRules>
     */
    public static function rulesForAttachmentUpdate(NovaRequest $request): array
    {
        return static::formatRules($request, self::newResource()
            ->updatePivotFields($request, $request->relatedResource)
            ->mapWithKeys(static fn ($field) => $field->getUpdateRules($request))
            ->all());
    }

    /**
     * Perform any final formatting of the given validation rules.
     *
     * @return array<array-key, mixed>
     *
     * @phpstan-return array<array-key, TFieldValidationRules>
     */
    protected static function formatRules(NovaRequest $request, array $rules): array
    {
        $replacements = array_filter([
            '{{resourceId}}' => str_replace(['\'', '"', ',', '\\'], '', $request->resourceId ?? ''),
        ]);

        if (empty($replacements)) {
            return $rules;
        }

        return collect($rules)->map(static function ($rules) use ($replacements) {
            return collect($rules)->map(static function ($rule) use ($replacements) {
                return \is_string($rule)
                    ? str_replace(array_keys($replacements), array_values($replacements), $rule)
                    : $rule;
            })->all();
        })->all();
    }

    /**
     * Get the validation attribute for a specific field.
     */
    public static function validationAttributeFor(NovaRequest $request, string $resourceName): Stringable|string
    {
        return self::newResource()
            ->availableFields($request)
            ->reject(static fn ($field) => $field instanceof PivotableField)
            ->firstWhere('resourceName', $resourceName)
            ->getValidationAttribute($request);
    }

    /**
     * Get the validation attachable attribute for a specific field.
     */
    public static function validationAttachableAttributeFor(NovaRequest $request, string $resourceName): Stringable|string
    {
        return self::newResource()
            ->availableFields($request)
            ->filter(static fn ($field) => $field instanceof PivotableField)
            ->firstWhere('resourceName', $resourceName)
            ->getValidationAttribute($request);
    }

    /**
     * Map field attributes to field names.
     *
     * @param  \Laravel\Nova\Resource|null  $resource
     * @return \Illuminate\Support\Collection<string, string>
     */
    private static function attributeNamesForFields(NovaRequest $request, ?Resource $resource = null): Collection
    {
        $resource = $resource ?: self::newResource();

        return $resource
            ->availableFields($request)
            ->reject(static fn ($field) => empty($field->name))
            ->mapWithKeys(fn ($field) => $field->getValidationAttributeNames($request));
    }

    /**
     * Handle any post-validation processing.
     *
     * @return void
     */
    protected static function afterValidation(NovaRequest $request, ValidatorContract $validator)
    {
        //
    }

    /**
     * Handle any post-creation validation processing.
     *
     * @return void
     */
    protected static function afterCreationValidation(NovaRequest $request, ValidatorContract $validator)
    {
        //
    }

    /**
     * Handle any post-update validation processing.
     *
     * @return void
     */
    protected static function afterUpdateValidation(NovaRequest $request, ValidatorContract $validator)
    {
        //
    }
}
