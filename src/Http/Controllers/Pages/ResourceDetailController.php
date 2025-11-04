<?php

namespace Laravel\Nova\Http\Controllers\Pages;

use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Nova\Http\Requests\ResourceDetailRequest;
use Laravel\Nova\Http\Resources\DetailViewResource;
use Laravel\Nova\Menu\Breadcrumb;
use Laravel\Nova\Menu\Breadcrumbs;
use Laravel\Nova\Nova;

class ResourceDetailController extends Controller
{
    /**
     * Show Resource Detail page using Inertia.
     */
    public function __invoke(ResourceDetailRequest $request): Response
    {
        $resourceClass = $request->resource();

        return Inertia::render('Nova.Detail', [
            'breadcrumbs' => $this->breadcrumbs($request),
            'resourceName' => $resourceClass::uriKey(),
            'resourceId' => $request->resourceId,
        ]);
    }

    /**
     * Get breadcrumb menu for the page.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    protected function breadcrumbs(ResourceDetailRequest $request): Breadcrumbs
    {
        $resource = transform(DetailViewResource::make(), static function (DetailViewResource $detail) use ($request) {
            $detail->authorizedResourceForRequest($request, $resource = $detail->newResourceWith($request));

            return $resource;
        });

        return Breadcrumbs::make([
            Breadcrumb::make(Nova::__('Resources')),
            Breadcrumb::resource($request->resource()),
            Breadcrumb::make(Nova::__(':resource Details: :title', [
                'resource' => $resource::singularLabel(),
                'title' => $resource->title(),
            ])),
        ]);
    }
}
