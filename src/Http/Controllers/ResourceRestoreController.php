<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\RestoreResourceRequest;
use Laravel\Nova\Jobs\RestoreResources;

class ResourceRestoreController extends Controller
{
    /**
     * Restore the given resource(s).
     */
    public function __invoke(RestoreResourceRequest $request): Response
    {
        RestoreResources::dispatchSync($request, $request->resource());

        return response()->noContent(200);
    }
}
