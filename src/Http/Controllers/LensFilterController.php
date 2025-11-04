<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;

class LensFilterController extends Controller
{
    /**
     * List the lenses for the given resource.
     */
    public function index(NovaRequest $request): JsonResponse
    {
        return response()->json(
            $request->newResource()
                ->availableLenses($request)
                ->first(static fn ($lens) => $lens->uriKey() === $request->lens)
                ->availableFilters($request)
        );
    }
}
