<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Contracts\PivotableField;
use Laravel\Nova\Http\Requests\NovaRequest;

use function Orchestra\Sidekick\Eloquent\model_exists;

class AttachableController extends Controller
{
    /**
     * List the available related resources for a given resource.
     */
    public function __invoke(NovaRequest $request): array
    {
        $field = $request->newResource()
                    ->availableFieldsOnIndexOrDetail($request)
                    ->filterForManyToManyRelations()
                    ->filter(static function ($field) use ($request) {
                        return $field->resourceName === $request->field && // @phpstan-ignore property.notFound
                            $field->component === $request->component &&
                            $field->attribute === $request->viaRelationship;
                    })->first();

        abort_if(\is_null($field), 404);

        $withTrashed = $this->shouldIncludeTrashed(
            $request, $associatedResource = $field->resourceClass // @phpstan-ignore property.notFound
        );

        $model = filled($request->resourceId) ? $request->findModelOrFail() : $request->model();

        $shouldReorderAttachableValues = $field->shouldReorderAttachableValues($request) && ! $associatedResource::usesScout();

        return [
            'resources' => $field->buildAttachableQuery($request, $withTrashed) // @phpstan-ignore argument.templateType
                ->tap($this->getAttachableQueryResolver($request, $field))
                ->get()
                ->mapInto($field->resourceClass)  // @phpstan-ignore property.notFound
                ->filter->authorizedToAttach($request, $model)
                ->map(static fn ($resource) => $field->formatAttachableResource($request, $resource))
                ->when(
                    $shouldReorderAttachableValues, static fn ($collection) => $collection->sortBy('display', SORT_NATURAL | SORT_FLAG_CASE)
                )->values(),
            'withTrashed' => $withTrashed,
            'softDeletes' => $associatedResource::softDeletes(),
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

        if ($request->current && $associatedResource::softDeletes()) {
            $associatedModel = $associatedModel->newQueryWithoutScopes()->find($request->current);

            /** @phpstan-ignore method.notFound */
            return $associatedModel ? $associatedModel->trashed() : false;
        }

        return false;
    }

    /**
     * Get attachable query resolver.
     *
     * @param  \Laravel\Nova\Fields\Field&\Laravel\Nova\Contracts\PivotableField  $field
     * @return callable(\Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Contracts\Database\Query\Builder):void
     */
    protected function getAttachableQueryResolver(NovaRequest $request, PivotableField $field)
    {
        return static function ($query) use ($request, $field) {
            if (
                $request->first === 'true'
                || $field->allowDuplicateRelations /** @phpstan-ignore property.notFound */
                || ! model_exists($relatedModel = $request->findModel())
            ) {
                return;
            }

            $query->whereNotExists(static function ($query) use ($field, $relatedModel) {
                /** @phpstan-ignore property.notFound */
                $relation = $relatedModel->{$field->manyToManyRelationship}();

                return $relation->applyDefaultPivotQuery($query)
                        ->select($relation->getRelatedPivotKeyName())
                        ->whereColumn($relation->getQualifiedRelatedKeyName(), $relation->getQualifiedRelatedPivotKeyName());
            });
        };
    }
}
