<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\DeleteLensResourceRequest;
use Laravel\Nova\Jobs\DeleteResources;

class LensResourceDestroyController extends Controller
{
    /**
     * Destroy the given resource(s).
     */
    public function __invoke(DeleteLensResourceRequest $request): Response
    {
        DeleteResources::dispatchSync($request);

        return response()->noContent(200);
    }
}
