<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\DashboardMetricRequest;

class DashboardMetricController extends Controller
{
    /**
     * Get the specified metric's value.
     */
    public function __invoke(DashboardMetricRequest $request): JsonResponse
    {
        return response()->json([
            'value' => $request->metric()->resolve($request),
        ]);
    }
}
