<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\ResourceUpdateOrUpdateAttachedRequest;
use Laravel\Nova\Http\Resources\UpdateViewResource;

class UpdateFieldController extends Controller
{
    /**
     * List the update fields for the given resource.
     */
    public function __invoke(ResourceUpdateOrUpdateAttachedRequest $request): JsonResponse
    {
        return UpdateViewResource::make()->toResponse($request);
    }

    /**
     * Synchronize the field for updating.
     */
    public function sync(ResourceUpdateOrUpdateAttachedRequest $request): JsonResponse
    {
        $resource = UpdateViewResource::make()->newResourceWith($request);

        return response()->json(
            $resource->updateFields($request)
                ->filter(static function ($field) use ($request) {
                    return $request->query('field') === $field->attribute &&
                            $request->query('component') === $field->dependentComponentKey();
                })->each->syncDependsOn($request)
                ->first()
        );
    }
}
