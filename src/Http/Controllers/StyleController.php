<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Style;

class StyleController extends Controller
{
    /**
     * Serve the requested stylesheet.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function __invoke(NovaRequest $request): Style
    {
        $asset = collect(Nova::allStyles())
            ->filter(static fn ($asset) => $asset->name() === $request->style)
            ->first();

        abort_if(\is_null($asset), 404);

        return $asset;
    }
}
