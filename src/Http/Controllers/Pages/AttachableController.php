<?php

namespace Laravel\Nova\Http\Controllers\Pages;

use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Nova\Http\Requests\ResourceCreateOrAttachRequest;
use Laravel\Nova\Menu\Breadcrumb;
use Laravel\Nova\Menu\Breadcrumbs;
use Laravel\Nova\Nova;

class AttachableController extends Controller
{
    /**
     * Show Resource Attach page using Inertia.
     */
    public function __invoke(ResourceCreateOrAttachRequest $request): Response
    {
        $resourceClass = $request->resource();

        $isPolymorphic = static function ($query) {
            return \is_null($query) || \in_array($query, [true, 1, '1']);
        };

        $parentResource = $request->findResourceOrFail();

        return Inertia::render('Nova.Attach', [
            'breadcrumbs' => $this->breadcrumbs($request),
            'resourceName' => $resourceClass::uriKey(),
            'resourceId' => $request->resourceId,
            'relatedResourceName' => $request->relatedResource,
            'viaRelationship' => $request->query('viaRelationship'),
            'polymorphic' => $isPolymorphic($request->query('polymorphic')),
            'viaResource' => $parentResource::uriKey(),
            'viaResourceId' => $parentResource->resource->getKey(),
            'parentResource' => [
                'name' => $parentResource->singularLabel(),
                'display' => $parentResource->title(),
            ],
        ]);
    }

    /**
     * Get breadcrumb menu for the page.
     */
    protected function breadcrumbs(ResourceCreateOrAttachRequest $request): Breadcrumbs
    {
        $resourceClass = $request->resource();
        $relatedResourceClass = $request->relatedResource();

        return Breadcrumbs::make([
            Breadcrumb::make(Nova::__('Resources')),
            Breadcrumb::resource($resourceClass),
            Breadcrumb::resource($request->findResourceOrFail()),
            Breadcrumb::make(Nova::__('Attach :resource', ['resource' => $relatedResourceClass::singularLabel()])),
        ]);
    }
}
