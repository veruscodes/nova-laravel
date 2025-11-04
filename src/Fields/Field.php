<?php

namespace Laravel\Nova\Fields;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Tappable;
use JsonSerializable;
use Laravel\Nova\Contracts\Resolvable;
use Laravel\Nova\Exceptions\NovaException;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\HasHelpText;
use Laravel\Nova\Util;
use Stringable;

use function Orchestra\Sidekick\is_safe_callable;

/**
 * @phpstan-type TFieldValidationRules \Stringable|string|\Illuminate\Contracts\Validation\ValidationRule|\Illuminate\Contracts\Validation\Rule|\Illuminate\Contracts\Validation\InvokableRule|(callable(string, mixed, \Closure):(void))
 * @phpstan-type TValidationRules array<int, TFieldValidationRules>|TFieldValidationRules
 *
 * @method static static make(\Stringable|string $name, string|callable|object|null $attribute = null, callable|null $resolveCallback = null)
 */
#[\AllowDynamicProperties]
abstract class Field extends FieldElement implements JsonSerializable, Resolvable
{
    use Conditionable;
    use DependentFields;
    use HandlesValidation;
    use HasHelpText;
    use MutableFields;
    use PeekableFields;
    use PreviewableFields;
    use SupportsFullWidthFields;
    use Tappable;

    public const LEFT_ALIGN = 'left';

    public const CENTER_ALIGN = 'center';

    public const RIGHT_ALIGN = 'right';

    /**
     * The displayable name of the field.
     *
     * @var string
     */
    public $name;

    /**
     * The attribute / column name of the field.
     *
     * @var string
     */
    public $attribute;

    /**
     * The value displayed to the user.
     *
     * @var string|null
     */
    public $displayedAs;

    /**
     * The callback to be used to resolve the field's display value.
     *
     * @var (callable(mixed, mixed, string):(mixed))|null
     */
    public $displayCallback;

    /**
     * Indicates whether the display value has been customized by the user.
     *
     * @var bool
     */
    public $usesCustomizedDisplay = false;

    /**
     * The callback to be used to resolve the field's value.
     *
     * @var (callable(mixed, mixed, ?string):(mixed))|null
     */
    public $resolveCallback;

    /**
     * The callback to be used to hydrate the model attribute.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent, string, string):(mixed))|null
     */
    public $fillCallback;

    /**
     * Indicates if the field should be sortable.
     *
     * @var bool
     */
    public $sortable = false;

    /**
     * Indicates if the field is nullable.
     *
     * @var bool
     */
    public $nullable = false;

    /**
     * Values which will be replaced to null.
     *
     * @var (callable():(array<int, mixed>|mixed))|array<int, mixed>|mixed
     */
    public $nullValues = [''];

    /**
     * Indicates if the field was resolved as a pivot field.
     *
     * @var bool
     */
    public $pivot = false;

    /**
     * The accessor that should be used to refer as a pivot field.
     *
     * @var string|null
     */
    public $pivotAccessor;

    /**
     * The text alignment for the field's text in tables.
     *
     * @var string
     */
    public $textAlign = 'left';

    /**
     * Indicates if the field should allow its whitespace to be wrapped.
     *
     * @var bool
     */
    public $wrapping = false;

    /**
     * Indicates if the field label and form element should sit on top of each other.
     *
     * @var bool
     */
    public $stacked = false;

    /**
     * The custom components registered for fields.
     *
     * @var array<class-string<\Laravel\Nova\Fields\Field>, string>
     */
    public static $customComponents = [];

    /**
     * The callback used to determine if the field is required.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool|null
     */
    public $requiredCallback;

    /**
     * The resource associated with the field.
     *
     * @var \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object|array
     */
    public $resource;

    /**
     * Indicates whether the field is visible.
     *
     * @var bool
     */
    public $visible = true;

    /**
     * The placeholder for the field.
     *
     * @var string|null
     */
    public $placeholder;

    /**
     * Indicated whether the field should show its label.
     *
     * @var bool
     */
    public $withLabel = true;

    /**
     * Indicated whether the field should display as though it is inline.
     *
     * @var bool
     */
    public $inline = false;

    /**
     * Indicated whether the field should display as though it is compact.
     *
     * @var bool
     */
    public $compact = false;

    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     * @param  string|callable|object|null  $attribute
     * @param  (callable(mixed, mixed, ?string):(mixed))|null  $resolveCallback
     */
    public function __construct($name, mixed $attribute = null, ?callable $resolveCallback = null)
    {
        $this->name = $name;
        $this->resolveCallback = $resolveCallback;

        $this->default(null);

        if ($attribute instanceof Closure || is_safe_callable($attribute)) {
            $this->computedCallback = $attribute;
            $this->attribute = 'ComputedField';
        } else {
            $this->attribute = $attribute ?? str_replace(' ', '_', Str::lower($name));
        }
    }

    /**
     * Stack the label above the field.
     *
     * @return $this
     */
    public function stacked()
    {
        $this->stacked = true;

        return $this;
    }

    /**
     * Resolve the field's value for display.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object|array  $resource
     */
    public function resolveForDisplay($resource, ?string $attribute = null): void
    {
        $this->resource = $resource;

        $attribute ??= $this->attribute;

        if (! $this->displayCallback) {
            $this->resolve($resource, $attribute);
        } elseif (\is_callable($this->displayCallback)) {
            if ($this->isComputed()) {
                $this->value = \call_user_func($this->computedCallback, $resource);
            }

            tap($this->value ?? $this->resolveAttribute($resource, $attribute), function ($value) use (
                $resource,
                $attribute
            ) {
                $this->value = $value;
                $this->resolveUsingDisplayCallback($value, $resource, $attribute);
            });
        }
    }

    /**
     * Resolve the field's value using the display callback.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object  $resource
     */
    protected function resolveUsingDisplayCallback(mixed $value, $resource, string $attribute): void
    {
        $this->usesCustomizedDisplay = true;
        $this->displayedAs = \call_user_func($this->displayCallback, $value, $resource, $attribute);
    }

    /**
     * Resolve the field's value.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object|array  $resource
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        $this->resource = $resource;

        $attribute ??= $this->attribute;

        if ($this->isComputed()) {
            $this->value = \call_user_func($this->computedCallback, $resource);

            return;
        }

        if (! $this->resolveCallback) {
            $this->value = $this->resolveAttribute($resource, $attribute);
        } elseif (\is_callable($this->resolveCallback)) {
            tap($this->resolveAttribute($resource, $attribute), function ($value) use ($resource, $attribute) {
                $this->value = \call_user_func($this->resolveCallback, $value, $resource, $attribute);
            });
        }
    }

    /**
     * Resolve the default value for an Action field.
     */
    public function resolveForAction(NovaRequest $request): void
    {
        if (! \is_null($this->value)) {
            return;
        }

        if (is_safe_callable($this->defaultCallback)) {
            $this->defaultCallback = \call_user_func($this->defaultCallback, $request);
        }

        $this->value = $this->defaultCallback;
    }

    /**
     * Resolve the given attribute from the given resource.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object|array  $resource
     */
    protected function resolveAttribute($resource, string $attribute): mixed
    {
        return Util::value(data_get($resource, str_replace('->', '.', $attribute)));
    }

    /**
     * Define the callback that should be used to display the field's value.
     *
     * @param  callable(mixed, mixed, string):mixed  $displayCallback
     * @return $this
     */
    public function displayUsing(callable $displayCallback)
    {
        $this->displayCallback = $displayCallback;

        return $this;
    }

    /**
     * Define the callback that should be used to resolve the field's value.
     *
     * @param  callable(mixed, mixed, ?string):mixed  $resolveCallback
     * @return $this
     */
    public function resolveUsing(callable $resolveCallback)
    {
        $this->resolveCallback = $resolveCallback;

        return $this;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     * @return mixed
     */
    public function fill(NovaRequest $request, object $model)
    {
        return $this->fillInto($request, $model, $this->attribute);
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     * @return mixed
     */
    public function fillForAction(NovaRequest $request, object $model)
    {
        return $this->fill($request, $model);
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     * @return mixed
     */
    public function fillInto(NovaRequest $request, object $model, string $attribute, ?string $requestAttribute = null)
    {
        return $this->fillAttribute($request, $requestAttribute ?? $this->attribute, $model, $attribute);
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     * @return mixed
     */
    protected function fillAttribute(NovaRequest $request, string $requestAttribute, object $model, string $attribute)
    {
        if (isset($this->fillCallback)) {
            return \call_user_func($this->fillCallback, $request, $model, $attribute, $requestAttribute);
        }

        return $this->fillAttributeFromRequest($request, $requestAttribute, $model, $attribute);
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     * @return mixed
     */
    protected function fillAttributeFromRequest(NovaRequest $request, string $requestAttribute, object $model, string $attribute)
    {
        if ($request->exists($requestAttribute)) {
            tap($request->input($requestAttribute), function ($value) use ($model, $attribute) {
                $value = $this->isValidNullValue($value) ? null : $value;

                $this->fillModelWithData($model, $value, $attribute);
            });
        }
    }

    /**
     * Fill the model's attribute with data.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     */
    public function fillModelWithData(object $model, mixed $value, string $attribute): void
    {
        $attributes = [str_replace('.', '->', $attribute) => $value];

        $model->forceFill($attributes);
    }

    /**
     * Determine if the field supports null values.
     */
    protected function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Set field compact value.
     *
     * @return $this
     */
    public function compact(bool $compact = true)
    {
        $this->compact = $compact;

        return $this;
    }

    /**
     * Determine if the given value is considered a valid null value
     * if the field supports them.
     *
     * @deprecated 4.14.0 Use "isValidNullValue()"
     */
    #[\Deprecated('Use `isValidNullValue()` method instead', since: '4.14.0')]
    protected function isNullValue(mixed $value): bool
    {
        return $this->isValidNullValue($value);
    }

    /**
     * Determine if the given value is considered a valid null value
     * if the field supports them.
     */
    public function isValidNullValue(mixed $value): bool
    {
        if (! $this->isNullable()) {
            return false;
        }

        return $this->valueIsConsideredNull($value);
    }

    /**
     * Determine if the given value is considered null.
     */
    protected function valueIsConsideredNull(mixed $value): bool
    {
        return \is_callable($this->nullValues) ? \call_user_func($this->nullValues, $value) : \in_array(
            $value,
            (array) $this->nullValues
        );
    }

    /**
     * Specify a callback that should be used to hydrate the model attribute for the field.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent, string, string):mixed)|null  $fillCallback
     * @return $this
     */
    public function fillUsing(?callable $fillCallback)
    {
        $this->fillCallback = $fillCallback;

        return $this;
    }

    /**
     * Specify that this field should be sortable.
     *
     * @return $this
     */
    public function sortable(bool $value = true)
    {
        if (! $this->isComputed()) {
            $this->sortable = $value;
        }

        return $this;
    }

    /**
     * Return the sortable uri key for the field.
     */
    public function sortableUriKey(): string
    {
        return $this->attribute;
    }

    /**
     * Indicate that the field should be nullable.
     *
     * @param  (callable():(array<int, mixed>))|array<int, mixed>|mixed  $values
     * @return $this
     */
    public function nullable(bool $nullable = true, mixed $values = null)
    {
        $this->nullable = $nullable;

        if ($values !== null) {
            $this->nullValues($values);
        }

        return $this;
    }

    /**
     * Specify nullable values.
     *
     * @param  (callable():(array<int, mixed>))|array<int, mixed>|mixed  $values
     * @return $this
     */
    public function nullValues(mixed $values)
    {
        $this->nullValues = $values;

        return $this;
    }

    /**
     * Get the component name for the field.
     *
     * @return string
     */
    public function component()
    {
        if (isset(static::$customComponents[$this::class])) {
            return static::$customComponents[$this::class];
        }

        return $this->component;
    }

    /**
     * Set the component that should be used by the field.
     */
    public static function useComponent(string $component): void
    {
        static::$customComponents[\get_called_class()] = $component;
    }

    /**
     * Set the text alignment of the field.
     *
     * @return $this
     */
    public function textAlign(string $alignment)
    {
        $this->textAlign = $alignment;

        return $this;
    }

    /**
     * Set the callback used to determine if the field is required.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool  $callback
     * @return $this
     */
    public function required(callable|bool $callback = true)
    {
        $this->requiredCallback = $callback;

        return $this;
    }

    /**
     * Determine if the field is required.
     */
    public function isRequired(NovaRequest $request): bool
    {
        return with($this->requiredCallback, function ($callback) use ($request) {
            if ($callback === true || (\is_callable($callback) && \call_user_func($callback, $request))) {
                return true;
            }

            if (! empty($this->attribute) && \is_null($callback)) {
                if ($request->isResourceIndexRequest() || $request->isLensRequest() || $request->isActionRequest()) {
                    return \in_array('required', $this->getCreationRules($request)[$this->attribute]);
                }

                if ($request->isCreateOrAttachRequest()) {
                    return \in_array('required', $this->getCreationRules($request)[$this->attribute]);
                }

                if ($request->isUpdateOrUpdateAttachedRequest()) {
                    return \in_array('required', $this->getUpdateRules($request)[$this->attribute]);
                }
            }

            return false;
        });
    }

    /**
     * Set the width for the help text tooltip.
     *
     * @return never
     *
     * @throws \Laravel\Nova\Exceptions\HelperNotSupported
     */
    public function helpWidth(string|int $helpWidth)
    {
        throw NovaException::helperNotSupported(__METHOD__, __CLASS__);
    }

    /**
     * Return the width of the help text tooltip.
     *
     * @return never
     *
     * @throws \Laravel\Nova\Exceptions\HelperNotSupported
     */
    public function getHelpWidth()
    {
        throw NovaException::helperNotSupported(__METHOD__, __CLASS__);
    }

    /**
     * Set the placeholder text for the field if supported.
     *
     * @return $this
     */
    public function placeholder(Stringable|string|null $text)
    {
        $this->placeholder = $text;

        return $this;
    }

    /**
     * Set the field to be visible on the form.
     *
     * @return $this
     */
    public function show()
    {
        $this->visible = true;

        return $this;
    }

    /**
     * Set the field to be hidden on the form.
     *
     * @return $this
     */
    public function hide()
    {
        $this->visible = false;

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
        return with(app(NovaRequest::class), function ($request) {
            $value = $this->isValidNullValue($this->value) ? null : $this->value;

            return array_merge([
                'attribute' => $this->attribute,
                'component' => $this->component(),
                'compact' => $this->compact,
                'displayedAs' => $this->displayedAs,
                'fullWidth' => $this->fullWidth,
                'helpText' => $this->getHelpText(),
                'indexName' => $this->name,
                'inline' => $this->inline,
                'name' => $this->name,
                'nullable' => $this->nullable,
                'panel' => $this->panel?->name,
                'placeholder' => $this->placeholder,
                'prefixComponent' => true,
                'writable' => $this->isWritable($request),
                'readonly' => $this->isReadonly($request),
                'required' => $this->isRequired($request),
                'sortable' => $this->sortable,
                'sortableUriKey' => $this->sortableUriKey(),
                'stacked' => $this->stacked,
                'textAlign' => $this->textAlign,
                'uniqueKey' => \sprintf(
                    '%s-%s-%s',
                    $this->attribute,
                    Str::slug($this->panel?->name ?? 'default'),
                    $this->component()
                ),
                'usesCustomizedDisplay' => $this->usesCustomizedDisplay,
                'validationKey' => $this->validationKey(),
                'value' => $value ?? $this->resolveDefaultValue($request),
                'visible' => $this->visible,
                'withLabel' => $this->withLabel,
                'wrapping' => $this->wrapping,
            ], $this->serializeDependentField($request), $this->meta());
        });
    }
}
