<?php

namespace Laravel\Nova\Http\Requests;

use Closure;
use Illuminate\Support\Collection;

class DeleteLensResourceRequest extends LensResourceDeletionRequest
{
    /**
     * Get the selected models for the action in chunks.
     *
     * @param  \Closure(\Illuminate\Support\Collection):void  $callback
     */
    public function chunks(int $count, Closure $callback): void
    {
        $this->chunkWithAuthorization($count, $callback, function ($models) {
            return $this->deletableModels($models);
        });
    }

    /**
     * Get the models that may be deleted.
     */
    protected function deletableModels(Collection $models): Collection
    {
        return $models->mapInto($this->resource())
            ->filter->authorizedToDelete($this)
            ->map->model();
    }
}
