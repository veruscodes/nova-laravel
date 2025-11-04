<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\ResourceCreateOrAttachRequest;
use Laravel\Nova\Http\Resources\CreateViewResource;
use Laravel\Nova\Http\Resources\ReplicateViewResource;

class CreationFieldController extends Controller
{
    /**
     * List the creation fields for the given resource.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function __invoke(ResourceCreateOrAttachRequest $request): JsonResponse
    {
        if ($request->has('fromResourceId')) {
            return ReplicateViewResource::make($request->fromResourceId)->toResponse($request);
        }

        return CreateViewResource::make()->toResponse($request);
    }
}
