<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;

class SoftDeleteStatusController extends Controller
{
    /**
     * Determine if the resource is soft deleting.
     */
    public function __invoke(NovaRequest $request): JsonResponse
    {
        $resourceClass = $request->resource();

        return response()->json(['softDeletes' => $resourceClass::softDeletes()]);
    }
}
