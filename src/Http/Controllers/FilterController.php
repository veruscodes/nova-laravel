<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;

class FilterController extends Controller
{
    /**
     * List the filters for the given resource.
     */
    public function __invoke(NovaRequest $request): JsonResponse
    {
        return response()->json($request->newResource()->availableFilters($request));
    }
}
