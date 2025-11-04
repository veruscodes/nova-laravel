<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\ResourceCreateOrAttachRequest;
use Laravel\Nova\Http\Resources\CreateViewResource;
use Laravel\Nova\Http\Resources\ReplicateViewResource;

class CreationFieldSyncController extends Controller
{
    /**
     * Synchronize the field for creation view.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function __invoke(ResourceCreateOrAttachRequest $request): JsonResponse
    {
        $resource = $request->has('fromResourceId')
            ? ReplicateViewResource::make($request->fromResourceId)->newResourceWith($request)
            : CreateViewResource::make()->newResourceWith($request);

        return response()->json(
            $resource->creationFields($request)
                ->filter(static function ($field) use ($request) {
                    return $request->query('field') === $field->attribute &&
                            $request->query('component') === $field->dependentComponentKey();
                })->each->syncDependsOn($request)
                ->first()
        );
    }
}
