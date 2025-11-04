<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\ResourceIndexRequest;
use Laravel\Nova\Http\Resources\IndexViewResource;

class ResourceIndexController extends Controller
{
    /**
     * List the resources for administration.
     */
    public function __invoke(ResourceIndexRequest $request): JsonResponse
    {
        return IndexViewResource::make()->toResponse($request);
    }
}
