<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\ResourceIndexRequest;

class ResourceCountController extends Controller
{
    /**
     * Get the resource count for a given query.
     */
    public function __invoke(ResourceIndexRequest $request): JsonResponse
    {
        return response()->json(['count' => $request->toCount()]);
    }
}
