<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\ResourceCreateOrAttachRequest;
use Laravel\Nova\Http\Resources\CreationPivotFieldResource;

class CreationPivotFieldController extends Controller
{
    /**
     * List the pivot fields for the given resource and relation.
     */
    public function __invoke(ResourceCreateOrAttachRequest $request): JsonResponse
    {
        return CreationPivotFieldResource::make()->toResponse($request);
    }

    /**
     * Synchronize the pivot field for creation.
     */
    public function sync(ResourceCreateOrAttachRequest $request): JsonResponse
    {
        $resource = CreationPivotFieldResource::make()->newResourceWith($request);

        return response()->json(
            $resource->creationPivotFields(
                $request, $request->relatedResource
            )->filter(static function ($field) use ($request) {
                return $request->query('field') === $field->attribute &&
                        $request->query('component') === $field->dependentComponentKey();
            })->applyDependsOn($request)
            ->first()
        );
    }
}
