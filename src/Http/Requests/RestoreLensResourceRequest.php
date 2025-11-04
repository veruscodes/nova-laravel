<?php

namespace Laravel\Nova\Http\Requests;

use Closure;
use Illuminate\Support\Collection;
use Laravel\Nova\TrashedStatus;

class RestoreLensResourceRequest extends LensResourceDeletionRequest
{
    /**
     * Get the selected models for the action in chunks.
     *
     * @param  \Closure(\Illuminate\Support\Collection):void  $callback
     */
    public function chunks(int $count, Closure $callback): void
    {
        $this->chunkWithAuthorization($count, $callback, function ($models) {
            return $this->restorableModels($models);
        });
    }

    /**
     * Get the models that may be restored.
     */
    protected function restorableModels(Collection $models): Collection
    {
        return $models->mapInto($this->resource())
            ->filter->isSoftDeleted()
            ->filter->authorizedToRestore($this)
            ->map->model();
    }

    /**
     * Get the trashed status of the request.
     */
    public function trashed(): TrashedStatus
    {
        return TrashedStatus::WITH;
    }
}
