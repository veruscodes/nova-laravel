<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\ResourcePeekRequest;

class ResourcePeekController extends Controller
{
    /**
     * Preview the resource for administration.
     */
    public function __invoke(ResourcePeekRequest $request): JsonResponse
    {
        $resource = $request->newResourceWith(tap($request->findModelQuery(), static function ($query) use ($request) {
            $resourceClass = $request->resource();
            $resourceClass::detailQuery($request, $query);
        })->firstOrFail());

        $resource->authorizeToView($request);

        return response()->json([
            'title' => (string) $resource->title(),
            'resource' => $resource->serializeForPeeking($request),
        ]);
    }
}
