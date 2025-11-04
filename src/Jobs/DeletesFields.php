<?php

namespace Laravel\Nova\Jobs;

use Laravel\Nova\DeleteField;
use Laravel\Nova\Http\Requests\NovaRequest;

trait DeletesFields
{
    /**
     * Delete the deletable fields on the given model / resource.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    protected function forceDeleteFields(NovaRequest $request, $model): void
    {
        $this->deleteFields($request, $model, false);
    }

    /**
     * Delete the deletable fields on the given model / resource.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    protected function deleteFields(NovaRequest $request, $model, bool $skipSoftDeletes = true): void
    {
        if ($skipSoftDeletes && $request->newResourceWith($model)->softDeletes()) {
            return;
        }

        $request->newResourceWith($model)
                    ->deletableFields($request)
                    ->filter->isPrunable()
                    ->each(static function ($field) use ($request, $model) {
                        DeleteField::forRequest($request, $field, $model);
                    });
    }
}
