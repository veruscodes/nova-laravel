<?php

namespace Laravel\Nova\Http\Controllers\Pages;

use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Nova\Http\Requests\ResourceCreateOrAttachRequest;
use Laravel\Nova\Menu\Breadcrumb;
use Laravel\Nova\Menu\Breadcrumbs;
use Laravel\Nova\Nova;

class ResourceReplicateController extends Controller
{
    /**
     * Show Resource Replicate page using Inertia.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function __invoke(ResourceCreateOrAttachRequest $request): Response
    {
        abort_unless($request->findModelQuery()->exists(), 404);

        $resourceClass = $request->resource();

        return Inertia::render('Nova.Replicate', [
            'breadcrumbs' => $this->breadcrumbs($request),
            'resourceName' => $resourceClass::uriKey(),
            'resourceId' => $request->resourceId,
            'viaResource' => $request->query('viaResource') ?? '',
            'viaResourceId' => $request->query('viaResourceId') ?? '',
            'viaRelationship' => $request->query('viaRelationship') ?? '',
        ]);
    }

    /**
     * Get breadcrumb menu for the page.
     */
    protected function breadcrumbs(ResourceCreateOrAttachRequest $request): Breadcrumbs
    {
        $resourceClass = $request->resource();

        return Breadcrumbs::make([
            Breadcrumb::make(Nova::__('Resources')),
            Breadcrumb::resource($resourceClass),
            Breadcrumb::resource($request->findResourceOrFail()),
            Breadcrumb::make(Nova::__('Replicate :resource', ['resource' => $resourceClass::singularLabel()])),
        ]);
    }
}
