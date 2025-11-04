<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\ForceDeleteLensResourceRequest;
use Laravel\Nova\Jobs\ForceDeleteResources;

class LensResourceForceDeleteController extends Controller
{
    /**
     * Force delete the given resource(s).
     */
    public function __invoke(ForceDeleteLensResourceRequest $request): Response
    {
        ForceDeleteResources::dispatchSync($request);

        return response()->noContent(200);
    }
}
