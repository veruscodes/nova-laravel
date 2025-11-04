<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Http\Requests\NovaRequest;

class FieldController extends Controller
{
    /**
     * Retrieve the given field for the given resource.
     */
    public function __invoke(NovaRequest $request): JsonResponse
    {
        $resource = $request->newResource();

        $fields = $request->relatable
            ? $resource->availableFieldsOnIndexOrDetail($request)->whereInstanceOf(RelatableField::class)
            : $resource->availableFields($request);

        return response()->json(
            $fields->findFieldByAttributeOrFail($request->field)
        );
    }
}
