<?php

namespace Laravel\Nova\Http\Controllers;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Util;

class MorphedResourceAttachController extends ResourceAttachController
{
    /**
     * {@inheritDoc}
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphToMany  $relationship
     */
    #[\Override]
    protected function initializePivot(NovaRequest $request, $relationship): Model|Pivot
    {
        $model = tap($request->findResourceOrFail(), static function ($resource) use ($request) {
            abort_unless($resource->hasRelatableFieldOrRelationship($request, $request->viaRelationship), 404);
        })->model();

        $parentKey = $request->resourceId;
        $relatedKey = $request->input($request->relatedResource);

        $parentKeyName = $relationship->getParentKeyName();
        $relatedKeyName = $relationship->getRelatedKeyName();

        if ($parentKeyName !== $request->model()->getKeyName()) {
            $parentKey = $request->findModelOrFail()->{$parentKeyName};
        }

        if ($relatedKeyName !== $request->newRelatedResource()::newModel()->getKeyName()) {
            $relatedKey = $request->findRelatedModelOrFail()->{$relatedKeyName};
        }

        $pivot = $relationship->newPivot($relationship->getDefaultPivotAttributes(), false);

        Util::expectPivotModel($pivot)->forceFill([
            $relationship->getForeignPivotKeyName() => $parentKey,
            $relationship->getRelatedPivotKeyName() => $relatedKey,
            $relationship->getMorphType() => $model->{$request->viaRelationship}()->getMorphClass(),
        ]);

        if ($relationship->withTimestamps) {
            $pivot->forceFill([
                $relationship->createdAt() => new DateTime,
                $relationship->updatedAt() => new DateTime,
            ]);
        }

        return $pivot;
    }
}
