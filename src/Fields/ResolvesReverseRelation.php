<?php

namespace Laravel\Nova\Fields;

use Illuminate\Database\Eloquent\Relations\Relation;
use Laravel\Nova\Http\Requests\NovaRequest;

trait ResolvesReverseRelation
{
    /**
     * The reverse relation for the related resource.
     *
     * @var string|null
     */
    public $reverseRelation = null;

    /**
     * Determine if the field is the reverse relation of a showed index view.
     */
    public function isReverseRelation(NovaRequest $request): bool
    {
        if (! $request->viaResource || ($this->resourceName && $this->resourceName !== $request->viaResource)) {
            return false;
        }

        $reverse = $this->getReverseRelation($request);

        return $reverse === $request->viaRelationship;
    }

    /**
     * Get reverse relation field name.
     */
    public function getReverseRelation(NovaRequest $request): string
    {
        if (\is_null($this->reverseRelation)) {
            $viaModel = forward_static_call(
                [$resourceClass = $request->viaResource(), 'newModel']
            );

            $viaResource = $resourceClass::make($viaModel);

            $resource = $request->newResource();

            $this->reverseRelation = $viaResource->availableFields($request)
                ->filter(function ($field) use ($viaModel, $resource) {
                    if (! isset($field->resourceName) || $field->resourceName !== $resource::uriKey()) {
                        return false;
                    }

                    if (! $field instanceof MorphMany
                        && ! $field instanceof HasMany
                        && ! $field instanceof HasOne) {
                        return false;
                    }

                    if ($field instanceof HasOne && $field->ofManyRelationship()) {
                        return false;
                    }

                    $model = $resource->model();

                    if (! method_exists($viaModel, $field->attribute) || ! method_exists($model, $this->attribute)) {
                        return false;
                    }

                    $relation = $viaModel->{$field->attribute}();

                    return $this->getRelationForeignKeyName($relation) === $this->getRelationForeignKeyName(
                        $resource->model()->{$this->attribute}()
                    );
                })->first(static fn ($field) => $field->attribute === $request->viaRelationship)
                ->attribute ?? '';
        }

        return $this->reverseRelation;
    }

    /**
     * Get foreign key name for relation.
     */
    protected function getRelationForeignKeyName(Relation $relation): string
    {
        return method_exists($relation, 'getForeignKeyName')
            ? $relation->getForeignKeyName()
            : $relation->getForeignKey();
    }
}
