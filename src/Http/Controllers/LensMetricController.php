<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\LensMetricRequest;

class LensMetricController extends Controller
{
    /**
     * List the metrics for the given resource.
     */
    public function index(LensMetricRequest $request): JsonResponse
    {
        return response()->json(
            $request->availableMetrics()
        );
    }

    /**
     * Get the specified metric's value.
     */
    public function show(LensMetricRequest $request): JsonResponse
    {
        return response()->json([
            'value' => $request->metric()->resolve($request),
        ]);
    }
}
