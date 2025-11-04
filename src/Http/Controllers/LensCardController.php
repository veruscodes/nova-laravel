<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\LensCardRequest;

class LensCardController extends Controller
{
    /**
     * List the cards for the given lens.
     */
    public function __invoke(LensCardRequest $request): JsonResponse
    {
        return response()->json(
            $request->availableCards()
        );
    }
}
