<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\DashboardRequest;
use Laravel\Nova\Http\Resources\DashboardViewResource;

class DashboardController extends Controller
{
    /**
     * Return the details for the Dashboard.
     */
    public function __invoke(DashboardRequest $request, string $dashboard = 'main'): JsonResponse
    {
        return DashboardViewResource::make($dashboard)->toResponse($request);
    }
}
