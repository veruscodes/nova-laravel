<?php

namespace Laravel\Nova;

use Closure;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Actionable;
use Laravel\Nova\Contracts\BehavesAsPanel;
use Laravel\Nova\Contracts\Cover;
use Laravel\Nova\Contracts\Deletable;
use Laravel\Nova\Contracts\Downloadable;
use Laravel\Nova\Contracts\ListableField;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Contracts\Resolvable;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphMany;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Unfillable;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Tabs\TabsGroup;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * @phpstan-import-type TFields from \Laravel\Nova\Resource
 */
trait ResolvesFields
{
    /**
     * Resolve the index fields.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function indexFields(NovaRequest $request): FieldCollection
    {
        /** @phpstan-ignore return.type */
        return $this->availableFields($request)
            ->when($request->viaManyToMany(), $this->relatedFieldResolverCallback($request))
            ->filterForIndex($request, $this->resource)
            ->withoutListableFields()
            ->authorized($request)
            ->resolveForDisplay($this->resource);
    }

    /**
     * Resolve the detail fields.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function detailFields(NovaRequest $request): FieldCollection
    {
        /** @phpstan-ignore return.type */
        return $this->availableFields($request)
            ->when($request->viaManyToMany(), $this->fieldResolverCallback($request))
            ->when($this->shouldAddActionsField($request), fn ($fields) => $fields->push($this->actionEventsField()))
            ->filterForDetail($request, $this->resource)
            ->authorized($request)
            ->resolveForDisplay($this->resource);
    }

    /**
     * Resolve the authorized preview fields.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, TFields>
     */
    protected function previewFieldsCollection(NovaRequest $request): FieldCollection
    {
        // If the user has specified the `fieldsForPreview` method, we're going to ignore any fields
        // using `showOnPreview` inside the resource's `fields`, `fieldsForIndex`, and `fieldsForDetail` methods.
        if (method_exists($this, 'fieldsForPreview')) {
            return FieldCollection::make(array_values($this->filter($this->fieldsForPreview($request))));
        }

        return $this->buildAvailableFields($request, ['fieldsForIndex', 'fieldsForDetail'])
            ->when($request->viaManyToMany(), $this->fieldResolverCallback($request))
            ->flattenStackedFields()
            ->withoutResourceTools()
            ->withoutListableFields()
            ->filter(fn ($field) => $field->isShownOnPreview($request, $this->resource));
    }

    /**
     * Resolve the preview fields.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, TFields>
     */
    public function previewFields(NovaRequest $request): FieldCollection
    {
        return $this->previewFieldsCollection($request)
            ->authorized($request)
            ->resolveForDisplay($this->resource);
    }

    /**
     * Return the count of preview fields available.
     */
    public function previewFieldsCount(NovaRequest $request): int
    {
        return $this->previewFieldsCollection($request)
            ->authorized($request)
            ->count();
    }

    /**
     * Resolve the authorized preview fields.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    protected function peekableFieldsCollection(NovaRequest $request): FieldCollection
    {
        // If the user has specified the `fieldsForPeeking` method, we're going to ignore any fields
        // using `showWhenPeeking` inside the resource's `fields`, `fieldsForIndex`, and `fieldsForDetail` methods.
        if (method_exists($this, 'fieldsForPeeking')) {
            return FieldCollection::make(array_values($this->filter($this->fieldsForPeeking($request))));
        }

        return $this->buildAvailableFields($request, ['fieldsForIndex', 'fieldsForDetail'])
            ->when($request->viaManyToMany(), $this->fieldResolverCallback($request))
            ->flattenStackedFields()
            ->withoutResourceTools()
            ->withoutListableFields()
            ->filterForPeeking($request);
    }

    /**
     * Resolve the peekable fields.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function peekableFields(NovaRequest $request): FieldCollection
    {
        return $this->peekableFieldsCollection($request)
            ->authorized($request)
            ->resolveForDisplay($this->resource)
            ->each(static function (Field $field) {
                if (property_exists($field, 'copyable')) {
                    $field->copyable = false;
                }
            });
    }

    /**
     * Return the count of peekable fields available.
     */
    public function peekableFieldsCount(NovaRequest $request): int
    {
        return $this->peekableFieldsCollection($request)
            ->authorized($request)
            ->count();
    }

    /**
     * Resolve the deletable fields.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field&\Laravel\Nova\Contracts\Deletable>
     */
    public function deletableFields(NovaRequest $request): FieldCollection
    {
        /** @phpstan-ignore return.type */
        return $this->availableFieldsOnIndexOrDetail($request)
            ->when($request->viaManyToMany(), $this->fieldResolverCallback($request))
            ->reject(static fn ($field) => $field instanceof Unfillable)
            ->whereInstanceOf(Deletable::class)
            ->unique(static function ($field) {
                /** @var \Laravel\Nova\Fields\Field&\Laravel\Nova\Contracts\Deletable $field */
                return $field->attribute;
            })
            ->authorized($request)
            ->resolveForDisplay($this->resource);
    }

    /**
     * Resolve the downloadable fields.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field&\Laravel\Nova\Contracts\Downloadable>
     */
    public function downloadableFields(NovaRequest $request): FieldCollection
    {
        /** @phpstan-ignore return.type */
        return $this->availableFieldsOnIndexOrDetail($request)
            ->when($request->viaManyToMany(), $this->fieldResolverCallback($request))
            ->whereInstanceOf(Downloadable::class)
            ->unique(static function ($field) {
                /** @var \Laravel\Nova\Fields\Field&\Laravel\Nova\Contracts\Downloadable $field */
                return $field->attribute;
            })
            ->authorized($request)
            ->resolveForDisplay($this->resource);
    }

    /**
     * Resolve the filterable fields.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field&\Laravel\Nova\Contracts\FilterableField>
     */
    public function filterableFields(NovaRequest $request): FieldCollection
    {
        return $this->availableFieldsOnIndexOrDetail($request)
            ->when($request->viaManyToMany(), function ($fields) use ($request) {
                $relatedField = $request->findParentResource()->relatableField($request, $request->viaRelationship);

                if (! \is_null($relatedField)) {
                    $fields->prepend($relatedField);
                }

                return \call_user_func($this->relatedFieldResolverCallback($request), $fields);
            })
            ->flattenStackedFields()
            ->withOnlyFilterableFields()
            ->unique(static fn ($field) => $field->attribute)
            ->authorized($request);
    }

    /**
     * Get related field from resource by attribute.
     */
    public function relatableField(NovaRequest $request, string $attribute): ?Field
    {
        /** @phpstan-ignore return.type */
        return $this->availableFieldsOnIndexOrDetail($request)
            ->when($request->viaManyToMany(), $this->fieldResolverCallback($request))
            ->whereInstanceOf(RelatableField::class)
            ->when($this->shouldAddActionsField($request), fn ($fields) => $fields->push($this->actionEventsField()))
            ->first(static fn ($field) => $field->attribute === $attribute);
    }

    /**
     * Determine resource has relatable field by attribute.
     */
    public function hasRelatableField(NovaRequest $request, string $attribute): bool
    {
        return $this->relatableField($request, $attribute) !== null;
    }

    public function hasRelatableFieldOrRelationship(NovaRequest $request, string $attribute): bool
    {
        if ($this->hasRelatableField($request, $attribute)) {
            return true;
        }

        $model = $this->model() ?? static::newModel();
        $method = new ReflectionMethod($model, $attribute);

        return $method->getReturnType() instanceof ReflectionNamedType && is_subclass_of($method->getReturnType()->getName(), Relation::class);
    }

    /**
     * Determine if the resource should have an Action field.
     *
     * @return \Closure(mixed):(bool)
     */
    protected function shouldAddActionsField(NovaRequest $request): Closure
    {
        return function ($fields) use ($request) {
            return with(
                $this->actionEventsField(),
                static fn ($actionField) => \in_array(Actionable::class, class_uses_recursive(static::newModel())) && $actionField->authorizedToSee($request)
            ) && $fields->whereInstanceOf(MorphMany::class)
                ->filter(static fn ($field) => $field->resourceClass === Nova::actionResource())
                ->isEmpty();
        };
    }

    /**
     * Return a new Action field instance.
     */
    protected function actionEventsField(): MorphMany
    {
        return MorphMany::make(Nova::__('Action Events'), 'actions', Nova::actionResource())
            ->canSee(static fn ($request) => Nova::actionResource()::authorizedToViewAny($request))
            ->collapsable();
    }

    /**
     * Resolve the detail fields and assign them to their associated panel.
     *
     * @param  \Laravel\Nova\Resource  $resource
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function detailFieldsWithinPanels(NovaRequest $request, Resource $resource): FieldCollection
    {
        return $this->detailFields($request)
            ->assignDefaultPanel(
                $request->viaRelationship() && $request->isResourceDetailRequest()
                    ? Panel::defaultNameForViaRelationship($resource, $request)
                    : Panel::defaultNameForDetail($resource)
            );
    }

    /**
     * Resolve the creation fields.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function creationFields(NovaRequest $request): FieldCollection
    {
        $fields = $this->availableFields($request)
            ->authorized($request)
            ->onlyCreateFields($request, $this->resource)
            ->resolve($this->resource);

        return $request->viaRelationship()
            ? $this->withPivotFields($request, $fields->all())
            : $fields;
    }

    /**
     * Return the creation fields excluding any readonly ones.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function creationFieldsWithoutReadonly(NovaRequest $request): FieldCollection
    {
        return $this->creationFields($request)
            ->withoutReadonly($request);
    }

    /**
     * Resolve the creation fields and assign them to their associated panel.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function creationFieldsWithinPanels(NovaRequest $request): FieldCollection
    {
        return $this->creationFields($request)
            ->assignDefaultPanel(Panel::defaultNameForCreate($request->newResource()));
    }

    /**
     * Resolve the creation pivot fields for a related resource.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function creationPivotFields(NovaRequest $request, string $relatedResource): FieldCollection
    {
        return $this->resolvePivotFields($request, $relatedResource)
            ->onlyCreateFields($request, $this->resource);
    }

    /**
     * Resolve the update fields.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function updateFields(NovaRequest $request): FieldCollection
    {
        return $this->resolveFields($request)
            ->onlyUpdateFields($request, $this->resource);
    }

    /**
     * Return the update fields excluding any readonly ones.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function updateFieldsWithoutReadonly(NovaRequest $request): FieldCollection
    {
        return $this->updateFields($request)
            ->withoutReadonly($request);
    }

    /**
     * Resolve the update fields and assign them to their associated panel.
     *
     * @param  \Laravel\Nova\Resource|null  $resource
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function updateFieldsWithinPanels(NovaRequest $request, ?Resource $resource = null): FieldCollection
    {
        return $this->updateFields($request)
            ->assignDefaultPanel(Panel::defaultNameForUpdate($resource ?? $request->newResource()));
    }

    /**
     * Resolve the update pivot fields for a related resource.
     *
     * @param  string  $relatedResource
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function updatePivotFields(NovaRequest $request, $relatedResource): FieldCollection
    {
        return $this->resolvePivotFields($request, $relatedResource)
            ->onlyUpdateFields($request, $this->resource);
    }

    /**
     * Remove non-preview fields from the given collection.
     */
    protected function removeNonPreviewFields(NovaRequest $request, FieldCollection $fields): FieldCollection
    {
        return $fields->reject(function ($field) {
            return $field instanceof ListableField ||
                ($field instanceof ResourceTool || $field instanceof ResourceToolElement) ||
                $field->attribute === 'ComputedField' ||
                ($field instanceof ID && $field->attribute === $this->resource->getKeyName());
        });
    }

    /**
     * Resolve the given fields to their values.
     *
     * @param  (\Closure(\Laravel\Nova\Fields\FieldCollection):(\Laravel\Nova\Fields\FieldCollection))|null  $filter
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    protected function resolveFields(NovaRequest $request, ?Closure $filter = null): FieldCollection
    {
        $fields = $this->availableFields($request)->authorized($request);

        if (! \is_null($filter)) {
            $fields = \call_user_func($filter, $fields);
        }

        $fields->resolve($this->resource);

        return $request->viaRelationship()
            ? $this->withPivotFields($request, $fields->all())
            : $fields;
    }

    /**
     * Resolve the field for the given attribute.
     */
    public function resolveFieldForAttribute(NovaRequest $request, string $attribute): Field
    {
        return $this->resolveFields($request)->findFieldByAttribute($attribute);
    }

    /**
     * Resolve the inverse field for the given relationship attribute.
     *
     * This is primarily used for Relatable rule to check if has-one / morph-one relationships are "full".
     */
    public function resolveInverseFieldsForAttribute(NovaRequest $request, string $attribute, ?string $morphType = null): FieldCollection
    {
        $field = $this->availableFields($request)
            ->findFieldByAttribute($attribute);

        if (! (! \is_null($field) && $field->authorize($request) && isset($field->resourceClass))) {
            return new FieldCollection;
        }

        /** @var class-string<\Laravel\Nova\Resource> $relatedResource */
        $relatedResource = $field instanceof MorphTo
            ? Nova::resourceForKey($morphType ?? $request->{$attribute.'_type'})
            : ($field->resourceClass ?? null);

        $relatedResource = new $relatedResource($relatedResource::newModel());

        return $relatedResource->availableFields($request)->reject(static function ($relatedField) use ($field) {
            /** @phpstan-ignore isset.property, argument.type */
            return isset($relatedField->attribute) &&
                isset($field->inverse) &&
                $relatedField->attribute !== $field->inverse;
        })->filter(static fn ($field) => isset($field->resourceClass) && $field->resourceClass == $request->resource());
    }

    /**
     * Resolve the resource's avatar field.
     */
    public function resolveAvatarField(NovaRequest $request): ?Cover
    {
        return tap(
            $this->availableFields($request)
                ->whereInstanceOf(Cover::class)
                ->authorized($request)
                ->first(),
            function ($field) {
                if ($field instanceof Resolvable) {
                    $field->resolve($this->resource);
                }
            }
        );
    }

    /**
     * Resolve the resource's avatar URL, if applicable.
     */
    public function resolveAvatarUrl(NovaRequest $request): ?string
    {
        $field = $this->resolveAvatarField($request);

        return $field?->resolveThumbnailUrl() ?? null;
    }

    /**
     * Determine whether the resource's avatar should be rounded, if applicable.
     */
    public function resolveIfAvatarShouldBeRounded(NovaRequest $request): bool
    {
        $field = $this->resolveAvatarField($request);

        return $field?->isRounded() ?? false;
    }

    /**
     * Get the panels that are available for the given create request.
     *
     * @param  \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>|null  $fields
     * @return array<int, \Laravel\Nova\Panel>
     */
    public function availablePanelsForCreate(NovaRequest $request, ?FieldCollection $fields = null): array
    {
        $method = $this->fieldsMethod($request);

        $fields ??= FieldCollection::make(
            value(fn () => array_values($this->{$method}($request))) /** @phpstan-ignore argument.type */
        )->onlyCreateFields($request, $this->resource);

        return $this->resolvePanelsFromFields(
            $request,
            $fields,
            Panel::defaultNameForCreate($request->newResource())
        )->all();
    }

    /**
     * Get the panels that are available for the given update request.
     *
     * @param  \Laravel\Nova\Resource  $resource
     * @param  \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>|null  $fields
     * @return array<int, \Laravel\Nova\Panel>
     */
    public function availablePanelsForUpdate(NovaRequest $request, ?Resource $resource = null, ?FieldCollection $fields = null): array
    {
        $method = $this->fieldsMethod($request);

        $fields ??= FieldCollection::make(
            value(fn () => array_values($this->{$method}($request))) /** @phpstan-ignore argument.type */
        )->onlyUpdateFields($request, $this->resource);

        return $this->resolvePanelsFromFields(
            $request,
            $fields,
            Panel::defaultNameForUpdate($resource ?? $request->newResource())
        )->all();
    }

    /**
     * Get the panels that are available for the given detail request.
     *
     * @param  \Laravel\Nova\Resource  $resource
     * @param  \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>  $fields
     * @return array<int, \Laravel\Nova\Panel>
     */
    public function availablePanelsForDetail(NovaRequest $request, Resource $resource, FieldCollection $fields): array
    {
        return $this->resolvePanelsFromFields(
            $request,
            $fields,
            $request->viaRelationship() && $request->isResourceDetailRequest()
                ? Panel::defaultNameForViaRelationship($resource, $request)
                : Panel::defaultNameForDetail($resource)
        )->all();
    }

    /**
     * Get the fields that are available for the given request.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function availableFields(NovaRequest $request): FieldCollection
    {
        $method = $this->fieldsMethod($request);

        return FieldCollection::make(array_values($this->filter($this->{$method}($request))));
    }

    /**
     * Get the fields that are available on "index" or "detail" for the given request.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function availableFieldsOnIndexOrDetail(NovaRequest $request): FieldCollection
    {
        return $this->buildAvailableFields($request, ['fieldsForIndex', 'fieldsForDetail']);
    }

    /**
     * Get the fields that are available for the given request.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function buildAvailableFields(NovaRequest $request, array $methods): FieldCollection
    {
        $fields = collect([
            method_exists($this, 'fields') ? $this->fields($request) : [],
        ]);

        collect($methods)
            ->filter(fn ($method) => $method != 'fields' && method_exists($this, $method))
            ->each(function ($method) use ($request, $fields) {
                $fields->push([$this->{$method}($request)]);
            });

        return FieldCollection::make(array_values($this->filter($fields->flatten()->all())));
    }

    /**
     * Compute the method to use to get the available fields.
     */
    protected function fieldsMethod(NovaRequest $request): string
    {
        if ($request->isInlineCreateRequest() && method_exists($this, 'fieldsForInlineCreate')) {
            return 'fieldsForInlineCreate';
        }

        if ($request->isResourceIndexRequest() && method_exists($this, 'fieldsForIndex')) {
            return 'fieldsForIndex';
        }

        if ($request->isResourceDetailRequest() && method_exists($this, 'fieldsForDetail')) {
            return 'fieldsForDetail';
        }

        if ($request->isCreateOrAttachRequest() && method_exists($this, 'fieldsForCreate')) {
            return 'fieldsForCreate';
        }

        if ($request->isUpdateOrUpdateAttachedRequest() && method_exists($this, 'fieldsForUpdate')) {
            return 'fieldsForUpdate';
        }

        return 'fields';
    }

    /**
     * Merge the available pivot fields with the given fields.
     *
     * @param  array<int, \Laravel\Nova\Fields\Field>  $fields
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    protected function withPivotFields(NovaRequest $request, array $fields): FieldCollection
    {
        $pivotFields = $this->resolvePivotFields($request, $request->viaResource)->all();

        if ($index = $this->indexToInsertPivotFields($request, $fields)) {
            array_splice($fields, $index + 1, 0, $pivotFields);
        } else {
            $fields = array_merge($fields, $pivotFields);
        }

        return FieldCollection::make($fields);
    }

    /**
     * Resolve the pivot fields for the requested resource.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function resolvePivotFields(NovaRequest $request, string $relatedResource): FieldCollection
    {
        $fields = $this->pivotFieldsFor($request, $relatedResource);

        return FieldCollection::make($this->filter($fields->each(function ($field) {
            if ($field instanceof Resolvable) {
                $field->resolve(
                    $this->{$field->pivotAccessor} ?? $field->pivotRelation->newPivot($field->pivotRelation->getDefaultPivotAttributes(), false)
                );
            }
        })->authorized($request)->all()))->values();
    }

    /**
     * Get the pivot fields for the resource and relation.
     */
    protected function pivotFieldsFor(NovaRequest $request, string $relatedResource): FieldCollection
    {
        /** @var \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\BelongsToMany|\Laravel\Nova\Fields\MorphToMany> $fields */
        $fields = $this->availableFields($request)->filter(static function ($field) use ($relatedResource) {
            return ($field instanceof BelongsToMany || $field instanceof MorphToMany) &&
                isset($field->resourceName) && $field->resourceName == $relatedResource;
        });

        /** @var \Laravel\Nova\Fields\BelongsToMany|\Laravel\Nova\Fields\MorphToMany|null $field */
        $field = $fields->count() === 1
            ? $fields->first()
            : $fields->first(fn ($field) => $field->manyToManyRelationship === $request->viaRelationship);

        if ($field && isset($field->fieldsCallback)) {
            $model = $this->model() ?? static::newModel();
            $pivotRelation = $model->{$field->manyToManyRelationship}();
            $field->pivotAccessor = $pivotAccessor = $pivotRelation->getPivotAccessor();

            return FieldCollection::make(array_values(
                $this->filter(\call_user_func($field->fieldsCallback, $request, $this->resource))
            ))->each(static function ($field) use ($pivotAccessor, $pivotRelation) {
                $field->pivot = true;
                $field->pivotAccessor = $pivotAccessor;
                $field->pivotRelation = $pivotRelation;
            });
        }

        return FieldCollection::make();
    }

    /**
     * Get the pivot fields for the resource and relation from related relationship.
     */
    protected function relatedPivotFieldsFor(NovaRequest $request, string $relatedResource): FieldCollection
    {
        $resource = Nova::resourceInstanceForKey($relatedResource);

        /** @var \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\BelongsToMany|\Laravel\Nova\Fields\MorphToMany> $fields */
        $fields = $resource->availableFields($request)->filter(function ($field) {
            return ($field instanceof BelongsToMany || $field instanceof MorphToMany) &&
                isset($field->resourceName) && $field->resourceName == $this->uriKey();
        });

        /** @var \Laravel\Nova\Fields\BelongsToMany|\Laravel\Nova\Fields\MorphToMany|null $field */
        $field = $fields->count() === 1
            ? $fields->first()
            : $fields->first(static fn ($field) => $field->manyToManyRelationship === $request->viaRelationship);

        if ($field && isset($field->fieldsCallback)) {
            $pivotRelation = $resource->model()->{$field->manyToManyRelationship}();
            $field->pivotAccessor = $pivotAccessor = $pivotRelation->getPivotAccessor();

            return FieldCollection::make(array_values(
                $this->filter(\call_user_func($field->fieldsCallback, $request, $this->resource))
            ))->each(function ($field) use ($pivotAccessor, $pivotRelation) {
                $field->pivot = true;
                $field->pivotAccessor = $pivotAccessor;
                $field->pivotRelation = $pivotRelation;
            });
        }

        return FieldCollection::make();
    }

    /**
     * Get the index where the pivot fields should be spliced into the field array.
     *
     * @param  array<int, \Laravel\Nova\Fields\Field>  $fields
     */
    protected function indexToInsertPivotFields(NovaRequest $request, array $fields): ?int
    {
        foreach ($fields as $index => $field) {
            if (
                isset($field->resourceName) &&
                $field->resourceName == $request->viaResource
            ) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Get the displayable pivot model name from a field.
     */
    public function pivotNameForField(NovaRequest $request, string $field): ?string
    {
        $field = $this->availableFields($request)->findFieldByAttribute($field);

        if (! ($field instanceof BelongsToMany || $field instanceof MorphToMany)) {
            return self::DEFAULT_PIVOT_NAME;
        }

        return $field?->pivotName ?? null;
    }

    /**
     * Resolve available panels from fields.
     *
     * @param  \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>  $fields
     * @return \Illuminate\Support\Collection<int, \Laravel\Nova\Panel>
     */
    protected function resolvePanelsFromFields(NovaRequest $request, FieldCollection $fields, string $label): Collection
    {
        [$defaultFields, $fieldsWithPanels] = $fields->each(static function ($field) {
            if ($field instanceof BehavesAsPanel && ! $field->panel instanceof TabsGroup) {
                $field->asPanel();
            }
        })->partition(static fn ($field) => ! isset($field->panel) || blank($field->panel->name));

        $panels = $fieldsWithPanels->groupBy(static fn ($field) => (string) $field->panel)
            ->transform(static fn ($fields, $name) => match (true) {
                $fields[0]->panel instanceof TabsGroup => TabsGroup::mutate($name, $fields), // @phpstan-ignore argument.type
                default => Panel::mutate($name, $fields),  // @phpstan-ignore argument.type
            })->toBase();

        if ($panels->where('component', 'tabs')->isNotEmpty()) {
            [$relationshipUnderTabs, $panels] = $panels->partition(
                static fn ($panel) => $panel->component === 'relationship-panel' && $panel->meta['fields'][0]->panel instanceof TabsGroup
            );

            $panels->transform(static function ($panel, $key) use ($relationshipUnderTabs) {
                if ($panel->component === 'tabs') {
                    $fields = $panel->meta['fields'];

                    $relationshipUnderTabs
                        ->filter(static function ($relation) use ($fields) {
                            return $fields[0]->panel === $relation->meta['fields'][0]->panel;
                        })->each(static function ($relation) use ($fields) {
                            $fields[] = $relation->meta['fields'][0];
                        });

                    TabsGroup::hydrate($panel, $fields); // @phpstan-ignore argument.type
                }

                return $panel;
            });
        }

        return $this->panelsWithDefaultLabel(
            $panels, // @phpstan-ignore argument.type
            $defaultFields->values(),
            $label
        );
    }

    /**
     * Return the panels for this request with the default label.
     *
     * @param  \Illuminate\Support\Collection<int, \Laravel\Nova\Panel>  $panels
     * @param  \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>  $fields
     * @return \Illuminate\Support\Collection<int, \Laravel\Nova\Panel>
     */
    protected function panelsWithDefaultLabel(Collection $panels, FieldCollection $fields, string $label): Collection
    {
        return $panels->values()
            ->when($panels->where('name', $label)->isEmpty(), static function ($panels) use ($label, $fields) {
                if ($fields->isNotEmpty()) {
                    $panels->prepend(Panel::makeDefault($label, $fields));
                } elseif ($panels->isNotEmpty() && \in_array($panels->first()->component, ['tabs-panel'])) {
                    $panels->prepend(Panel::makeDefault($label, [])->withToolbar());
                }

                return $panels;
            })->tap(static function ($panels) {
                if (! $panels->first()) {
                    return;
                }

                $firstPanel = $panels->first()->component !== 'tabs'
                    ? $panels->first()->withToolbar()
                    : $panels->where('component', 'tabs')->first();

                if (property_exists($firstPanel, 'collapsable') && $firstPanel->collapsable === true) {
                    trigger_deprecation('laravel/nova', '5.0', 'Using `collapsible()` on First Panel is not supported');
                }
            });
    }

    /**
     * Return the callback used for resolving fields.
     *
     * @return \Closure(\Laravel\Nova\Fields\FieldCollection):\Laravel\Nova\Fields\FieldCollection
     */
    protected function fieldResolverCallback(NovaRequest $request): Closure
    {
        return function ($fields) use ($request) {
            $fields = $fields->values()->all();
            $pivotFields = $this->pivotFieldsFor($request, $request->viaResource)->all();

            if (! \is_null($index = $this->indexToInsertPivotFields($request, $fields))) {
                array_splice($fields, $index + 1, 0, $pivotFields);
            } else {
                $fields = array_merge($fields, $pivotFields);
            }

            return FieldCollection::make($fields);
        };
    }

    /**
     * Return the callback used for resolving fields with pivot from related relationship.
     *
     * @return \Closure(\Laravel\Nova\Fields\FieldCollection):\Laravel\Nova\Fields\FieldCollection
     */
    protected function relatedFieldResolverCallback(NovaRequest $request): Closure
    {
        return function ($fields) use ($request) {
            $fields = $fields->values()->all();
            $pivotFields = $this->relatedPivotFieldsFor($request, $request->viaResource)->all();

            if (! \is_null($index = $this->indexToInsertPivotFields($request, $fields))) {
                array_splice($fields, $index + 1, 0, $pivotFields);
            } else {
                $fields = array_merge($fields, $pivotFields);
            }

            return FieldCollection::make($fields);
        };
    }
}
