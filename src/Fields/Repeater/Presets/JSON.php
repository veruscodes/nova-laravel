<?php

namespace Laravel\Nova\Fields\Repeater\Presets;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Fields\Repeater\RepeatableCollection;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Support\Fluent;

class JSON implements Preset
{
    /**
     * Save the field value to permanent storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     */
    public function set(
        NovaRequest $request,
        string $requestAttribute,
        $model,
        string $attribute,
        RepeatableCollection $repeatables,
        string|int|null $uniqueField
    ): callable {
        $repeaterItemsInput = collect($request->input($requestAttribute));
        $existingItems = collect();

        if ($uniqueField) {
            $existingItems = $this->withoutRemovedItems(collect($model->{$attribute}), $repeaterItemsInput, $uniqueField);
        }

        $model->setAttribute($attribute, null);

        /** @var \Illuminate\Support\Collection<int, \Closure> $callbacks */
        $callbacks = collect($repeaterItemsInput)
            ->map(function ($item, $itemIndex) use ($request, $requestAttribute, $repeatables, $model, $attribute, $existingItems, $uniqueField) {
                $repeatable = $repeatables->findByKey($item['type']);
                $fields = FieldCollection::make($repeatable->fields($request));
                $data = new Fluent;

                // For each field collection, return the callbacks and set the data on the fluent instance, and then return a function
                // that invokes all the callbacks;
                $fieldsCallbacks = $fields
                    ->withoutUnfillable()
                    ->withoutMissingValues()
                    ->map(
                        static fn (Field $field) => $field->fillInto($request, $data, $field->attribute, "{$requestAttribute}.{$itemIndex}.fields.{$field->attribute}")
                    )->filter(static fn ($callback) => \is_callable($callback));

                if ($uniqueField) {
                    $this->upsertData($existingItems, $data, $uniqueField);
                    $this->cleanObsoleteFields($data, $fields);
                }

                $model->setAttribute("{$attribute}->{$itemIndex}->type", $repeatable->key());
                foreach ($data->getAttributes() as $k => $v) {
                    $model->setAttribute("{$attribute}->{$itemIndex}->fields->{$k}", $v);
                }

                return static function () use ($fieldsCallbacks) {
                    $fieldsCallbacks->each->__invoke();
                };
            });

        return static function () use ($callbacks) {
            $callbacks->each->__invoke();
        };
    }

    /**
     * Retrieve the value from storage and hydrate the field's value.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     */
    public function get(NovaRequest $request, $model, string $attribute, RepeatableCollection $repeatables): Collection
    {
        return RepeatableCollection::make($model->{$attribute})
            ->map(static fn ($block) => $repeatables->newRepeatableByKey($block['type'], $block['fields']));
    }

    /**
     * Filter removed items from collection.
     */
    protected function withoutRemovedItems(
        Collection $existingItems,
        Collection $inputRepeaterItems,
        string|int|null $uniqueField
    ): Collection {
        $deletableIds = $existingItems
            ->whereNotIn(
                "fields.{$uniqueField}",
                $inputRepeaterItems->map(static fn ($item) => $item['fields'][$uniqueField] ?? null)
                    ->filter()
                    ->all()
            );

        return $existingItems
            ->reject(
                static fn ($item) => $deletableIds->contains("fields.$uniqueField", $item['fields'][$uniqueField])
            );
    }

    /**
     * Handles generating the uniqueKey if needed and updates $data with existing attributes values.
     */
    protected function upsertData(Collection $existingItems, Fluent $data, string $uniqueField): void
    {
        if (! $data->value($uniqueField)) {
            $data->forceFill([$uniqueField => Str::uuid()]);
        }

        if (
            $existingItem = $existingItems->first(static function ($item) use ($data, $uniqueField) {
                return $item['fields'][$uniqueField] === $data->value($uniqueField);
            })
        ) {
            $data->forceFill(array_merge($existingItem['fields'], $data->getAttributes()));
        }
    }

    /**
     * Remove any attribute which is not present in the list of the repeatable's fields anymore.
     */
    protected function cleanObsoleteFields(Fluent $data, FieldCollection $fields): void
    {
        foreach ($data->getAttributes() as $attribute => $value) {
            if (\is_null($fields->findFieldByAttribute($attribute))) {
                $data->offsetUnset($attribute);
            }
        }
    }
}
