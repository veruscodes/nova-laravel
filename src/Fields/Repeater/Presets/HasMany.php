<?php

namespace Laravel\Nova\Fields\Repeater\Presets;

use Illuminate\Database\Eloquent\Relations\HasMany as EloquentHasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Fields\Repeater\RepeatableCollection;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Support\Fluent;

class HasMany implements Preset
{
    /**
     * Save the field value to permanent storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function set(
        NovaRequest $request,
        string $requestAttribute,
        $model,
        string $attribute,
        RepeatableCollection $repeatables,
        string|int|null $uniqueField
    ): callable {
        return function () use ($request, $requestAttribute, $model, $attribute, $repeatables, $uniqueField) {
            $repeaterItems = collect($request->input($requestAttribute));

            if (! $uniqueField) {
                $model->{$attribute}()->delete();
            } else {
                $this->deleteMissingRelations($attribute, $model, $repeaterItems, $uniqueField);
            }

            $repeaterItems->transform(static function ($item, $blockKey) use ($request, $requestAttribute, $repeatables) {
                $block = $repeatables->findByKey($item['type']);
                $fields = FieldCollection::make($block->fields($request));
                $data = Fluent::make();

                $callbacks = $fields
                    ->withoutUnfillable()
                    ->withoutMissingValues()
                    ->map(
                        static fn (Field $field) => $field->fillInto($request, $data, $field->attribute, "{$requestAttribute}.{$blockKey}.fields.{$field->attribute}")
                    )->filter(static fn ($callback) => \is_callable($callback))
                    ->toBase();

                return [$data, $callbacks, $item];
            })->each(function ($tuple) use ($model, $attribute, $uniqueField) {
                [$data, $callbacks, $row] = $tuple;

                if ($uniqueField) {
                    $this->upsertRelation($model, $data, $row, $uniqueField, $model->{$attribute}());
                } else {
                    $model->{$attribute}()->forceCreate($data->getAttributes());
                }

                $callbacks->each->__invoke();
            });
        };
    }

    /**
     * Retrieve the value from storage and hydrate the field's value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function get(NovaRequest $request, $model, string $attribute, RepeatableCollection $repeatables): Collection
    {
        return RepeatableCollection::make($model->{$attribute})
            ->map(static fn ($block) => $repeatables->newRepeatableByModel($block));
    }

    /**
     * Delete missing relations.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function deleteMissingRelations(string $attribute, $model, Collection $repeaterItems, string|int|null $uniqueField): void
    {
        /** @var \Illuminate\Database\Eloquent\Relations\HasMany $relation */
        $relation = $model->{$attribute}();

        $availableItems = $repeaterItems->map(
            static fn ($item) => $item['fields'][$uniqueField]
        )->all();

        $deletableIds = $relation->pluck($uniqueField)
            ->reject(static fn ($id) => \in_array($id, $availableItems));

        if ($deletableIds->isNotEmpty()) {
            $model->{$attribute}()->whereIn($uniqueField, $deletableIds)->delete();
        }
    }

    /**
     * Upsert relation.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function upsertRelation($model, Fluent $data, array $row, string|int|null $uniqueField, EloquentHasMany $relation): void
    {
        $model->unguarded(static function () use ($data, $row, $uniqueField, $relation) {
            $uniqueValue = $row['fields'][$uniqueField];

            $attributes = Arr::except($data->getAttributes(), $uniqueField);

            if (empty($uniqueValue)) {
                $relation->create($attributes);
            } else {
                $relation->updateOrCreate(
                    [$uniqueField => $uniqueValue], $attributes
                );
            }
        });
    }
}
