<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

class MorphableController extends Controller
{
    /**
     * List the available morphable resources for a given resource.
     */
    public function __invoke(NovaRequest $request): array
    {
        $relatedResource = Nova::resourceForKey($request->type);

        abort_if(\is_null($relatedResource), 403);

        $field = $request->newResource()
            ->availableFieldsOnIndexOrDetail($request)
            ->whereInstanceOf(RelatableField::class)
            ->findFieldByAttributeOrFail($request->field)
            ->applyDependsOn($request);

        $withTrashed = $this->shouldIncludeTrashed(
            $request, $relatedResource
        );

        $limit = $relatedResource::usesScout()
                    ? $relatedResource::$scoutSearchResults
                    : $relatedResource::$relatableSearchResults;

        $shouldReorderAssociatableValues = $field->shouldReorderAssociatableValues($request) && ! $relatedResource::usesScout();

        $query = method_exists($field, 'searchAssociatableQuery')
            ? $field->searchAssociatableQuery($request, $relatedResource, $withTrashed)
            : $field->buildAssociatableQuery($request, $relatedResource, $withTrashed);

        return [
            'resources' => $query->take($limit)
                ->get()
                ->mapInto($relatedResource)
                ->filter->authorizedToAdd($request, $request->model())
                ->map(static fn ($resource) => $field->formatMorphableResource($request, $resource, $relatedResource))
                ->when($shouldReorderAssociatableValues, static fn ($collection) => $collection->sortBy('display'))
                ->values(),
            'withTrashed' => $withTrashed,
            'softDeletes' => $relatedResource::softDeletes(),
        ];
    }

    /**
     * Determine if the query should include trashed models.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $associatedResource
     */
    protected function shouldIncludeTrashed(NovaRequest $request, string $associatedResource): bool
    {
        if ($request->withTrashed === 'true') {
            return true;
        }

        $associatedModel = $associatedResource::newModel();

        if ($request->current && empty($request->search) && $associatedResource::softDeletes()) {
            $associatedModel = $associatedModel->newQueryWithoutScopes()->find($request->current);

            /** @phpstan-ignore method.notFound */
            return $associatedModel ? $associatedModel->trashed() : false;
        }

        return false;
    }
}
