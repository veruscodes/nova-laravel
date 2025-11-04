<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\ResourceUpdateOrUpdateAttachedRequest;
use Laravel\Nova\Http\Resources\UpdatePivotFieldResource;

class UpdatePivotFieldController extends Controller
{
    /**
     * List the pivot fields for the given resource and relation.
     */
    public function __invoke(ResourceUpdateOrUpdateAttachedRequest $request): JsonResponse
    {
        return UpdatePivotFieldResource::make()->toResponse($request);
    }

    /**
     * Synchronize the pivot field for updating.
     */
    public function sync(ResourceUpdateOrUpdateAttachedRequest $request): JsonResponse
    {
        $resource = UpdatePivotFieldResource::make()->newResourceWith($request);

        return response()->json(
            $resource->updatePivotFields(
                $request, $request->relatedResource
            )->filter(static function ($field) use ($request) {
                return $request->query('field') === $field->attribute &&
                        $request->query('component') === $field->dependentComponentKey();
            })->applyDependsOn($request)
            ->first()
        );
    }
}
