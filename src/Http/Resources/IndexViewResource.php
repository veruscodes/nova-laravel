<?php

namespace Laravel\Nova\Http\Resources;

use Laravel\Nova\Http\Requests\ResourceIndexRequest;

class IndexViewResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Laravel\Nova\Http\Requests\ResourceIndexRequest  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = $this->authorizedResourceForRequest($request);

        [$paginator, $total, $sortable] = $request->searchIndex();

        return [
            'label' => $resource::label(),
            'resources' => $paginator->getCollection()->mapInto($resource)->map->serializeForIndex($request),
            'prevPageUrl' => $paginator->previousPageUrl(),
            'nextPageUrl' => $paginator->nextPageUrl(),
            'perPage' => $paginator->perPage(),
            'total' => $total,
            'softDeletes' => $resource::softDeletes(),
            'polling' => $resource::$polling,
            'pollingInterval' => $resource::$pollingInterval * 1000,
            'showPollingToggle' => $resource::$showPollingToggle,
            'sortable' => $sortable ?? true,
        ];
    }

    /**
     * Get authorized resource for the request.
     *
     * @return class-string<\Laravel\Nova\Resource>
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizedResourceForRequest(ResourceIndexRequest $request): string
    {
        return tap($request->resource(), static function ($resourceClass) use ($request) {
            abort_unless($resourceClass::authorizedToViewAny($request), 403);
        });
    }
}
