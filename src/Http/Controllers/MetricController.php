<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\MetricRequest;

class MetricController extends Controller
{
    /**
     * List the metrics for the given resource.
     */
    public function index(MetricRequest $request): JsonResponse
    {
        return response()->json(
            $request->availableMetrics()
        );
    }

    /**
     * Get the specified metric's value.
     */
    public function show(MetricRequest $request): JsonResponse
    {
        return response()->json([
            'value' => $request->metric()->resolve($request),
        ]);
    }
}
