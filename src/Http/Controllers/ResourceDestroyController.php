<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\DeleteResourceRequest;
use Laravel\Nova\Jobs\DeleteResources;
use Laravel\Nova\URL;

class ResourceDestroyController extends Controller
{
    /**
     * Destroy the given resource(s).
     */
    public function __invoke(DeleteResourceRequest $request): JsonResponse|Response
    {
        DeleteResources::dispatchSync($request, $request->resource());

        if ($request->isForSingleResource() && ! \is_null($redirect = $request->resource()::redirectAfterDelete($request))) {
            return response()->json([
                'redirect' => URL::make($redirect),
            ]);
        }

        return response()->noContent(200);
    }
}
