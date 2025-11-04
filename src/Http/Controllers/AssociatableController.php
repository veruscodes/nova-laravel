<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Http\Requests\NovaRequest;

class AssociatableController extends Controller
{
    /**
     * List the available related resources for a given resource.
     */
    public function __invoke(NovaRequest $request): array
    {
        $field = $request->newResource()
            ->availableFieldsOnIndexOrDetail($request)
            ->whereInstanceOf(RelatableField::class)
            ->findFieldByAttributeOrFail($request->field)
            ->applyDependsOn($request);

        $withTrashed = $this->shouldIncludeTrashed(
            $request, $associatedResource = $field->resourceClass
        );

        $limit = $associatedResource::usesScout()
            ? $associatedResource::$scoutSearchResults
            : $associatedResource::$relatableSearchResults;

        $shouldReorderAssociatableValues = $field->shouldReorderAssociatableValues($request) && ! $associatedResource::usesScout();

        $query = method_exists($field, 'searchAssociatableQuery')
            ? $field->searchAssociatableQuery($request, $associatedResource, $withTrashed)
            : $field->buildAssociatableQuery($request, $associatedResource, $withTrashed);

        return [
            'resources' => $query->take($limit)
                ->get()
                ->mapInto($associatedResource)
                ->when(
                    $request->isCreateOrAttachRequest() || $request->isUpdateOrUpdateAttachedRequest(),
                    static fn ($resources) => $resources->filter->authorizedToAdd($request, $request->model())
                )->map(static fn ($resource) => $field->formatAssociatableResource($request, $resource))
                ->when($shouldReorderAssociatableValues, static fn ($collection) => $collection->sortBy('display'))
                ->values(),
            'softDeletes' => $associatedResource::softDeletes(),
            'withTrashed' => $withTrashed,
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
