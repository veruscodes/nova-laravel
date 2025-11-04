<?php

namespace Laravel\Nova\Http\Resources;

use Laravel\Nova\Http\Requests\ResourceCreateOrAttachRequest;
use Laravel\Nova\Resource as NovaResource;

class CreationPivotFieldResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Laravel\Nova\Http\Requests\ResourceCreateOrAttachRequest  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->newResourceWith($request)
            ->creationPivotFields(
                $request,
                $request->relatedResource
            )->applyDependsOnWithDefaultValues($request)->all();
    }

    /**
     * Get current resource for the request.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function newResourceWith(ResourceCreateOrAttachRequest $request): NovaResource
    {
        return tap($request->newResourceWith($request->findModel()), static function ($resource) use ($request) {
            abort_unless($resource->hasRelatableFieldOrRelationship($request, $request->viaRelationship), 404);
        });
    }
}
