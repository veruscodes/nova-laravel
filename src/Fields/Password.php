<?php

namespace Laravel\Nova\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Laravel\Nova\Http\Requests\NovaRequest;

class Password extends Field
{
    use SupportsAutoCompletion;
    use SupportsDependentFields;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'password-field';

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

        $this->withoutAutoCompletion();
    }

    /**
     * Resolve the given attribute from the given resource.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object|array  $resource
     */
    #[\Override]
    protected function resolveAttribute($resource, string $attribute): mixed
    {
        return '';
    }

    /**
     * Resolve the default value for an Action field.
     */
    #[\Override]
    public function resolveForAction(NovaRequest $request): void
    {
        $this->value = '';
    }

    /**
     * Resolve the field's value for display.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object|array  $resource
     */
    #[\Override]
    public function resolveForDisplay($resource, ?string $attribute = null): void
    {
        $this->value = '';
    }

    /**
     * Fill the model's attribute with data.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     */
    #[\Override]
    public function fillModelWithData(object $model, mixed $value, string $attribute): void
    {
        if (! empty($value)) {
            $attributes = [str_replace('.', '->', $attribute) => Hash::make($value)];

            $model->forceFill($attributes);
        }
    }

    /**
     * Get the default disabled autocomplete value.
     */
    protected function defaultDisabledAutoCompleteValue(): string
    {
        return 'new-password';
    }
}
