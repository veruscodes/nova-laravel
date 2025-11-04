<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\LensCountRequest;

class LensResourceCountController extends Controller
{
    /**
     * Get the resource count for a given query.
     */
    public function __invoke(LensCountRequest $request): JsonResponse
    {
        return response()->json(['count' => $request->toCount()]);
    }
}
