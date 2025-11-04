<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Script;

class ScriptController extends Controller
{
    /**
     * Serve the requested script.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function __invoke(NovaRequest $request): Script
    {
        $asset = collect(Nova::allScripts())
            ->filter(static fn ($asset) => $asset->name() === $request->script)
            ->first();

        abort_if(\is_null($asset), 404);

        return $asset;
    }
}
