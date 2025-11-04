<?php

namespace Laravel\Nova\Fields;

use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Nova\Http\Requests\NovaRequest;
use Stringable;

/**
 * @phpstan-import-type TValidationRules from \Laravel\Nova\Fields\Field
 * @phpstan-import-type TFieldValidationRules from \Laravel\Nova\Fields\Field
 */
trait HandlesValidation
{
    /**
     * The validation attribute for the field.
     *
     * @var string|null
     */
    public $validationAttribute = null;

    /**
     * The validation rules for creation and updates.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(array|\Stringable|string|callable))|array|\Stringable|string
     *
     * @phpstan-var (callable(\Laravel\Nova\Http\Requests\NovaRequest):TValidationRules)|TValidationRules
     */
    public $rules = [];

    /**
     * The validation rules for creation.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(array|\Stringable|string|callable))|array|\Stringable|string
     *
     * @phpstan-var (callable(\Laravel\Nova\Http\Requests\NovaRequest):TValidationRules)|TValidationRules
     */
    public $creationRules = [];

    /**
     * The validation rules for updates.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(array|\Stringable|string|callable))|array|\Stringable|string
     *
     * @phpstan-var (callable(\Laravel\Nova\Http\Requests\NovaRequest):TValidationRules)|TValidationRules
     */
    public $updateRules = [];

    /**
     * Set the validation rules for the field.
     *
     * @no-named-arguments
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(array|\Stringable|string|callable))|array|\Stringable|string  ...$rules
     *
     * @phpstan-param (callable(\Laravel\Nova\Http\Requests\NovaRequest):TValidationRules)|TValidationRules ...$rules
     *
     * @return $this
     */
    public function rules($rules)
    {
        $parameters = \func_get_args();

        $this->rules = (
            $rules instanceof Rule ||
            $rules instanceof InvokableRule ||
            $rules instanceof ValidationRule ||
            \is_string($rules) ||
            \count($parameters) > 1
        ) ? $parameters : $rules;

        return $this;
    }

    /**
     * Get the validation rules for this field.
     *
     * @return array<array-key, array<int, mixed>>
     *
     * @phpstan-return array<string, array<int, TFieldValidationRules>>
     */
    public function getRules(NovaRequest $request): array
    {
        return [
            $this->attribute => \is_callable($this->rules) ? \call_user_func($this->rules, $request) : $this->rules,
        ];
    }

    /**
     * Get the creation rules for this field.
     *
     * @return array<array-key, mixed>
     *
     * @phpstan-return array<string, array<int, TFieldValidationRules>>
     */
    public function getCreationRules(NovaRequest $request): array
    {
        $rules = [
            $this->attribute => \is_callable($this->creationRules) ? \call_user_func(
                $this->creationRules,
                $request
            ) : $this->creationRules,
        ];

        return array_merge_recursive($this->getRules($request), $rules);
    }

    /**
     * Set the creation validation rules for the field.
     *
     * @no-named-arguments
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(array|\Stringable|string|callable))|array|\Stringable|string  ...$rules
     *
     * @phpstan-param (callable(\Laravel\Nova\Http\Requests\NovaRequest):TValidationRules)|TValidationRules ...$rules
     *
     * @return $this
     */
    public function creationRules($rules)
    {
        $parameters = \func_get_args();

        $this->creationRules = (
            $rules instanceof Rule ||
            $rules instanceof InvokableRule ||
            $rules instanceof ValidationRule ||
            \is_string($rules) ||
            \count($parameters) > 1
        ) ? $parameters : $rules;

        return $this;
    }

    /**
     * Get the update rules for this field.
     *
     * @return array<array-key, array<int, mixed>>
     *
     * @phpstan-return array<string, array<int, TFieldValidationRules>>
     */
    public function getUpdateRules(NovaRequest $request): array
    {
        $rules = [
            $this->attribute => \is_callable($this->updateRules) ? \call_user_func(
                $this->updateRules,
                $request
            ) : $this->updateRules,
        ];

        return array_merge_recursive($this->getRules($request), $rules);
    }

    /**
     * Set the creation validation rules for the field.
     *
     * @no-named-arguments
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(array|\Stringable|string|callable))|array|\Stringable|string  ...$rules
     *
     * @phpstan-param (callable(\Laravel\Nova\Http\Requests\NovaRequest):TValidationRules)|TValidationRules ...$rules
     *
     * @return $this
     */
    public function updateRules($rules)
    {
        $parameters = \func_get_args();

        $this->updateRules = (
            $rules instanceof Rule ||
            $rules instanceof InvokableRule ||
            $rules instanceof ValidationRule ||
            \is_string($rules) ||
            \count($parameters) > 1
        ) ? $parameters : $rules;

        return $this;
    }

    /**
     * Get the validation attribute for the field.
     */
    public function getValidationAttribute(NovaRequest $request): Stringable|string
    {
        return $this->validationAttribute ?? Str::singular($this->name);
    }

    /**
     * Get the validation attribute names for the field.
     *
     * @return array<string, string>
     */
    public function getValidationAttributeNames(NovaRequest $request): array
    {
        return [$this->validationKey() => $this->name];
    }

    /**
     * Return the validation key for the field.
     */
    public function validationKey(): string
    {
        return $this->attribute;
    }
}
