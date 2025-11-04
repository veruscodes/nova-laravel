<?php

namespace Laravel\Nova\Fields\Repeater;

use Illuminate\Support\Str;
use JsonSerializable;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Makeable;
use Laravel\Nova\Nova;

class Repeatable implements JsonSerializable
{
    use Makeable;

    /**
     * The collection of fields for the block.
     *
     * @var \Laravel\Nova\Fields\FieldCollection
     */
    public $fields;

    /**
     * The associated model class for the block. Only used for HasMany and MorphMany presets.
     *
     * @var string
     */
    public static $model;

    /**
     * The icon used to represent the block type in the UI.
     *
     * @var string
     */
    public static $icon = 'menu';

    /**
     * @var bool
     */
    public $confirmRemoval = false;

    /**
     * The dataset for repeatable.
     *
     * @var \Illuminate\Database\Eloquent\Model|array
     */
    private $data = [];

    /**
     * Create a new block instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model|array  $data
     */
    public function __construct($data = [])
    {
        $this->data = $data;
        $this->fields = new FieldCollection;
    }

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return Str::plural(Nova::humanize(\get_called_class()));
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return string
     */
    public static function singularLabel()
    {
        return Str::singular(static::label());
    }

    /**
     * Get the unique key for the block.
     *
     * @return string
     */
    public static function key()
    {
        return Str::singular(Str::kebab(class_basename(\get_called_class())));
    }

    /**
     * Resolve the values of the fields in the Repeatable.
     *
     * @return \Laravel\Nova\Fields\FieldCollection
     */
    public function resolveFields(NovaRequest $request)
    {
        return FieldCollection::make($this->fields($request))
            ->withoutMissingValues()
            ->each(function (Field $field) use ($request) {
                $field
                    ->compact()
                    ->resolve($this->data, $field->attribute);

                if (\is_null($field->value) && empty($this->data)) {
                    $field->value = $field->resolveDefaultCallback($request);
                }
            });
    }

    /**
     * Resolve the values of the fields in the Repeatable.
     *
     * @return \Laravel\Nova\Fields\FieldCollection
     */
    public function resolveFieldsForDisplay(NovaRequest $request)
    {
        return FieldCollection::make($this->fields($request))
            ->withoutMissingValues()
            ->each(function (Field $field) {
                $field->compact()->resolveForDisplay($this->data, $field->attribute);
            });
    }

    /**
     * Get the fields displayed by the block.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            //
        ];
    }

    /**
     * Ask the user to confirm before removing this block.
     */
    public function confirmRemoval(): Repeatable
    {
        $this->confirmRemoval = true;

        return $this;
    }

    /**
     * Set the data for the Repeatable.
     *
     * @param  \Illuminate\Database\Eloquent\Model|array  $data
     */
    public function setData($data): Repeatable
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $fields = with(app(NovaRequest::class), function ($request) {
            return $request->isFormRequest()
                ? $this->resolveFields($request)
                : $this->resolveFieldsForDisplay($request);
        });

        return [
            'icon' => static::$icon,
            'label' => static::label(),
            'singularLabel' => static::singularLabel(),
            'type' => static::key(),
            'fields' => $fields,
            'confirmBeforeRemoval' => $this->confirmRemoval,
        ];
    }
}
