<?php

namespace Laravel\Nova\Http\Requests;

use Closure;
use Illuminate\Support\Collection;
use Laravel\Nova\Resource;

class DetachResourceRequest extends DeletionRequest
{
    /**
     * Get the selected models for the action in chunks.
     *
     * @param  \Closure(\Illuminate\Support\Collection):void  $callback
     */
    public function chunks(int $count, Closure $callback): void
    {
        $parentResource = $this->findParentResourceOrFail();
        $model = $this->model();

        $this->toSelectedResourceQuery()->when(! $this->allResourcesSelected(), function ($query) {
            $query->whereKey($this->resources);
        })->chunkById($count, function ($models) use ($callback, $parentResource) {
            $models = $this->detachableModels($models, $parentResource);

            if ($models->isNotEmpty()) {
                $callback($models);
            }
        }, $model->getQualifiedKeyName(), $model->getKeyName());
    }

    /**
     * Get the models that may be detached.
     */
    protected function detachableModels(Collection $models, Resource $parentResource): Collection
    {
        return $models->filter(function ($model) use ($parentResource) {
            return $parentResource->authorizedToDetach($this, $model, $this->viaRelationship);
        });
    }
}
