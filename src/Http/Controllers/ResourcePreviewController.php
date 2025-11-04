<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\ResourcePreviewRequest;

class ResourcePreviewController extends Controller
{
    /**
     * Preview the resource for administration.
     */
    public function __invoke(ResourcePreviewRequest $request): JsonResponse
    {
        $resource = $request->newResourceWith(tap($request->findModelQuery(), static function ($query) use ($request) {
            $resourceClass = $request->resource();
            $resourceClass::detailQuery($request, $query);
        })->firstOrFail());

        $resource->authorizeToView($request);

        return response()->json([
            'title' => (string) $resource->title(),
            'resource' => $resource->serializeForPreview($request),
        ]);
    }
}
