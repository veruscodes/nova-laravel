<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\DetachResourceRequest;
use Laravel\Nova\Jobs\DetachResources;

class ResourceDetachController extends Controller
{
    /**
     * Detach the given resource(s).
     */
    public function __invoke(DetachResourceRequest $request): Response
    {
        DetachResources::dispatchSync($request);

        return response()->noContent(200);
    }
}
