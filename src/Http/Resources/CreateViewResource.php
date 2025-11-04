<?php

namespace Laravel\Nova\Http\Resources;

use Laravel\Nova\Http\Requests\ResourceCreateOrAttachRequest;
use Laravel\Nova\Resource as NovaResource;

class CreateViewResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Laravel\Nova\Http\Requests\ResourceCreateOrAttachRequest  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = $this->newResourceWith($request);

        $this->authorizedResourceForRequest($request);

        $fields = $resource->creationFieldsWithinPanels($request)->applyDependsOnWithDefaultValues($request);

        return [
            'fields' => $fields,
            'panels' => $resource->availablePanelsForCreate($request, $fields),
        ];
    }

    /**
     * Get current resource for the request.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function newResourceWith(ResourceCreateOrAttachRequest $request): NovaResource
    {
        return $request->newResource();
    }

    /**
     * Determine if resource is authorized for the request.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizedResourceForRequest(ResourceCreateOrAttachRequest $request): void
    {
        $resourceClass = $request->resource();

        $resourceClass::authorizeToCreate($request);
    }
}
