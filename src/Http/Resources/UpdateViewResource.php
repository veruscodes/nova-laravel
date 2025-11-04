<?php

namespace Laravel\Nova\Http\Resources;

use Laravel\Nova\Http\Requests\ResourceUpdateOrUpdateAttachedRequest;
use Laravel\Nova\Resource as NovaResource;

class UpdateViewResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Laravel\Nova\Http\Requests\ResourceUpdateOrUpdateAttachedRequest  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = $this->newResourceWith($request);

        $this->authorizedResourceForRequest($request, $resource);

        return [
            'title' => (string) $resource->title(),
            'fields' => $fields = $resource->updateFieldsWithinPanels($request, $resource)->applyDependsOnWithDefaultValues($request),
            'panels' => $resource->availablePanelsForUpdate($request, $resource, $fields),
        ];
    }

    /**
     * Get current resource for the request.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function newResourceWith(ResourceUpdateOrUpdateAttachedRequest $request): NovaResource
    {
        return $request->newResourceWith(
            tap($request->findModelQuery(), static function ($query) use ($request) {
                $resourceClass = $request->resource();
                $resourceClass::editQuery($request, $query);
            })->firstOrFail()
        );
    }

    /**
     * Determine if resource is authorized for the request.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizedResourceForRequest(ResourceUpdateOrUpdateAttachedRequest $request, NovaResource $resource): void
    {
        $resource->authorizeToUpdate($request);
    }
}
