<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\ForceDeleteResourceRequest;
use Laravel\Nova\Jobs\ForceDeleteResources;

class ResourceForceDeleteController extends Controller
{
    /**
     * Force delete the given resource(s).
     */
    public function __invoke(ForceDeleteResourceRequest $request): JsonResponse|Response
    {
        ForceDeleteResources::dispatchSync($request, $request->resource());

        if ($request->isForSingleResource() && ! \is_null($redirect = $request->resource()::redirectAfterDelete($request))) {
            return response()->json([
                'redirect' => $redirect,
            ]);
        }

        return response()->noContent(200);
    }
}
