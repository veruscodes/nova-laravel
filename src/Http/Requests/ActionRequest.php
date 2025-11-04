<?php

namespace Laravel\Nova\Http\Requests;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionModelCollection;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Support\Fluent;

/**
 * @property-read string|null $resources
 * @property-read string|null $pivotAction
 */
class ActionRequest extends NovaRequest
{
    use QueriesResources;

    /**
     * Get the action instance specified by the request.
     *
     * @return \Laravel\Nova\Actions\Action|\Laravel\Nova\Actions\DestructiveAction
     */
    public function action(): Action
    {
        return once(function () {
            $hasResources = ! empty($this->resources);

            return $this->availableActions()
                ->filter(
                    static fn ($action) => $hasResources ? true : $action->isStandalone()
                )->first(
                    fn ($action) => $action->uriKey() == $this->query('action')
                ) ?: abort($this->actionExists() ? 403 : 404);
        });
    }

    /**
     * Get the all actions for the request.
     */
    protected function resolveActions(): Collection
    {
        return $this->isPivotAction()
            ? $this->newResource()->resolvePivotActions($this)
            : $this->newResource()->resolveActions($this);
    }

    /**
     * Get the possible actions for the request.
     */
    protected function availableActions(): Collection
    {
        return $this->resolveActions()->filter->authorizedToSee($this)->values();
    }

    /**
     * Determine if the specified action exists at all.
     */
    protected function actionExists(): bool
    {
        return $this->resolveActions()
            ->contains(fn ($action) => $action->uriKey() == $this->query('action'));
    }

    /**
     * Determine if the action being executed is a pivot action.
     */
    public function isPivotAction(): bool
    {
        return $this->pivotAction === 'true';
    }

    /**
     * Get the selected models for the action in chunks.
     *
     * @param  \Closure(\Laravel\Nova\Actions\ActionModelCollection):mixed  $callback
     * @return array<int, mixed>
     */
    public function chunks(int $count, Closure $callback): array
    {
        $output = [];

        $this->toSelectedResourceQuery()
            ->cursor()
            ->chunk($count)
            ->each(function ($chunk) use ($callback, &$output) {
                $output[] = $callback($this->mapChunk($chunk));
            });

        return $output;
    }

    /**
     * Get the query for the models that were selected by the user.
     */
    public function toSelectedResourceQuery(): Builder
    {
        if ($this->allResourcesSelected()) {
            return $this->toQuery();
        }

        $query = $this->viaRelationship()
            ? $this->modelsViaRelationship()
            : $this->toQueryWithoutScopes()->whereKey(Arr::wrap($this->resources));

        return $query->tap(function ($query) {
            $query->latest($this->model()->getQualifiedKeyName());
        });
    }

    /**
     * Transform the request into a query without scope.
     */
    public function toQueryWithoutScopes(): Builder
    {
        return tap($this->newQueryWithoutScopes(), function ($query) {
            $resourceClass = $this->resource();
            $query->with($resourceClass::$with);

            if (! $this->allResourcesSelected() && $this->selectedResourceIds()->count() === 1) {
                $resourceClass::detailQuery($this, $query);
            } else {
                $resourceClass::indexQuery($this, $query);
            }
        });
    }

    /**
     * Get the query for the related models that were selected by the user.
     */
    protected function modelsViaRelationship(): Builder
    {
        $relation = tap($this->findParentResource(), function ($resource) {
            abort_unless($resource->hasRelatableFieldOrRelationship($this, $this->viaRelationship), 404);
        })->model()->{$this->viaRelationship}()->withoutGlobalScopes();

        if (isset($this->pivots) && ! empty($this->pivots)) {
            /** @var class-string<\Illuminate\Database\Eloquent\Relations\Pivot> $pivotClass */
            $pivotClass = $relation->getPivotClass();

            $relation->wherePivotIn((new $pivotClass)->getKeyName(), Arr::wrap($this->pivots));
        }

        return $relation->whereIn($this->model()->getQualifiedKeyName(), Arr::wrap($this->resources));
    }

    /**
     * Map the chunk of models into an appropriate state.
     *
     * @param  \Illuminate\Support\LazyCollection|\Illuminate\Database\Eloquent\Collection  $chunk
     * @return \Laravel\Nova\Actions\ActionModelCollection<array-key, \Illuminate\Database\Eloquent\Model>
     */
    protected function mapChunk($chunk): ActionModelCollection
    {
        return new ActionModelCollection(
            $this->isPivotAction()
                ? $chunk->map->{$this->pivotRelation()->getPivotAccessor()}
                : $chunk
        );
    }

    /**
     * Validate the given fields.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateFields(): void
    {
        $this->action()->validateFields($this);
    }

    /**
     * Resolve the fields for database storage using the request.
     */
    public function resolveFieldsForStorage(): array
    {
        return collect($this->resolveFields()->getAttributes())->map(static function ($attribute) {
            return $attribute instanceof UploadedFile ? $attribute->hashName() : $attribute;
        })->all();
    }

    /**
     * Resolve the fields using the request.
     */
    public function resolveFields(): ActionFields
    {
        return once(function () {
            $fields = new Fluent;

            $results = (new FieldCollection($this->action()->fields($this)))
                ->authorized($this)
                ->applyDependsOn($this)
                ->withoutReadonly($this)
                ->withoutUnfillable()
                ->mapWithKeys(fn ($field) => [
                    $field->attribute => $field->fillForAction($this, $fields),
                ]);

            return new ActionFields(
                collect($fields->getAttributes()),
                $results->filter(static fn ($field) => \is_callable($field))
            );
        });
    }

    /**
     * Get the key of model that lists the action on its dashboard.
     *
     * When running pivot actions, this is the key of the owning model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function actionableKey($model): string|int
    {
        return $this->isPivotAction()
            ? $model->{$this->pivotRelation()->getForeignPivotKeyName()}
            : $model->getKey();
    }

    /**
     * Get the model instance that lists the action on its dashboard.
     *
     * When running pivot actions, this is the owning model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function actionableModel()
    {
        return $this->isPivotAction()
            ? $this->newViaResource()->model()
            : $this->model();
    }

    /**
     * Get the key of model that is the target of the action.
     *
     * When running pivot actions, this is the key of the target model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return int
     */
    public function targetKey($model)
    {
        return $this->isPivotAction()
            ? $model->{$this->pivotRelation()->getRelatedPivotKeyName()}
            : $model->getKey();
    }

    /**
     * Get an instance of the target model of the action.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function targetModel()
    {
        return $this->isPivotAction() ? $this->pivotRelation()->newPivot() : $this->model();
    }

    /**
     * Get the many-to-many relationship for a pivot action.
     */
    public function pivotRelation(): MorphToMany|BelongsToMany|null
    {
        if ($this->isPivotAction()) {
            return tap($this->newViaResource(), function ($resource) {
                abort_unless($resource->hasRelatableFieldOrRelationship($this, $this->viaRelationship), 404);
            })->model()->{$this->viaRelationship}();
        }

        return null;
    }

    /** {@inheritDoc} */
    #[\Override]
    public function isActionRequest(): bool
    {
        return true;
    }
}
