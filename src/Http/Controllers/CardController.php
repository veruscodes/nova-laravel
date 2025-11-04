<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\CardRequest;

class CardController extends Controller
{
    /**
     * List the cards for the given resource.
     */
    public function __invoke(CardRequest $request): JsonResponse
    {
        return response()->json(
            $request->availableCards()
        );
    }
}
