<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\MetricRequest;

class DetailMetricController extends Controller
{
    /**
     * Get the specified metric's value.
     */
    public function __invoke(MetricRequest $request): JsonResponse
    {
        return response()->json([
            'value' => $request->detailMetric()->resolve($request),
        ]);
    }
}
