<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\ResourceDetailRequest;
use Laravel\Nova\Http\Resources\DetailViewResource;

class ResourceShowController extends Controller
{
    /**
     * Display the resource for administration.
     */
    public function __invoke(ResourceDetailRequest $request): JsonResponse
    {
        return DetailViewResource::make()->toResponse($request);
    }
}
