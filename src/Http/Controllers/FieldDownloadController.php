<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FieldDownloadController extends Controller
{
    /**
     * Download the given field's contents.
     */
    public function __invoke(NovaRequest $request): Response|RedirectResponse|BinaryFileResponse|StreamedResponse
    {
        $resource = $request->findResourceOrFail();

        $resource->authorizeToView($request);

        return $resource->downloadableFields($request)
            ->findFieldByAttributeOrFail($request->field)
            ->toDownloadResponse($request, $resource);
    }
}
