<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\RestoreLensResourceRequest;
use Laravel\Nova\Jobs\RestoreResources;

class LensResourceRestoreController extends Controller
{
    /**
     * Force delete the given resource(s).
     */
    public function __invoke(RestoreLensResourceRequest $request): Response
    {
        RestoreResources::dispatchSync($request);

        return response()->noContent(200);
    }
}
