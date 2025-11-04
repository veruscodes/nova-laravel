<?php

namespace Laravel\Nova\Fields;

use Illuminate\Http\Request;
use Laravel\Nova\Contracts\BehavesAsPanel;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Exceptions\NovaException;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Panel;
use Laravel\Nova\Resource;
use Laravel\Nova\Util;
use Stringable;

use function Orchestra\Sidekick\Eloquent\model_exists;

/**
 * @method static static make(mixed $name, string|null $attribute = null, string|null $resource = null)
 */
class HasOne extends Field implements BehavesAsPanel, RelatableField
{
    use FormatsRelatableDisplayValues;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'has-one-field';

    /**
     * The class name of the related resource.
     *
     * @var class-string<\Laravel\Nova\Resource>
     */
    public $resourceClass;

    /**
     * The URI key of the related resource.
     *
     * @var string
     */
    public $resourceName;

    /**
     * The displayable singular label of the relation.
     *
     * @var \Stringable|string
     */
    public $singularLabel;

    /**
     * The resolved HasOne Resource.
     *
     * @var \Laravel\Nova\Resource|null
     */
    public $hasOneResource = null;

    /**
     * The name of the Eloquent "has one" relationship.
     *
     * @var string
     */
    public $hasOneRelationship;

    /**
     * The key of the related Eloquent model.
     *
     * @var string|int|null
     */
    public $hasOneId = null;

    /**
     * The callback use to determine if the HasOne field has already been filled.
     *
     * @var callable(\Laravel\Nova\Http\Requests\NovaRequest):bool
     */
    public $filledCallback;

    /**
     * Determine one-of-many relationship.
     *
     * @var bool
     */
    protected $ofManyRelationship = false;

    /**
     * The cached field is required status.
     *
     * @var bool|null
     */
    protected $isRequired = null;

    /**
     * Indicates if the related resource can be viewed.
     *
     * @var bool
     */
    public $viewable = true;

    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     * @param  class-string<\Laravel\Nova\Resource>|null  $resource
     */
    public function __construct($name, ?string $attribute = null, ?string $resource = null)
    {
        parent::__construct($name, $attribute);

        $resource ??= ResourceRelationshipGuesser::guessResource($name);

        $this->resourceClass = $resource;
        $this->resourceName = $resource::uriKey();
        $this->hasOneRelationship = $this->attribute = $attribute ?? ResourceRelationshipGuesser::guessRelation($name);
        $this->singularLabel = $resource::singularLabel();

        $this->alreadyFilledWhen(function ($request) {
            $parentResource = Nova::resourceForKey($request->viaResource);

            if ($this->ofManyRelationship === false && $request->viaRelationship === $this->attribute && $request->viaResourceId) {
                $parent = $parentResource::newModel()
                            ->with($this->attribute)
                            ->find($request->viaResourceId);

                return model_exists($parent->{$this->attribute});
            }

            return false;
        })->showOnCreating(static fn ($request) => ! \in_array($request->relationshipType, ['hasOne', 'morphOne']))
        ->showOnUpdating(static fn ($request) => ! \in_array($request->relationshipType, ['hasOne', 'morphOne']))
        ->nullable();
    }

    /**
     * Make one-of-many relationship field.
     *
     * @param  \Stringable|string  $name
     * @param  class-string<\Laravel\Nova\Resource>|null  $resource
     */
    public static function ofMany($name, ?string $attribute = null, ?string $resource = null): static
    {
        return tap(new static($name, $attribute, $resource), static function ($field) {
            $field->ofManyRelationship = true;
            $field->readonly();
            $field->onlyOnDetail();
        });
    }

    /**
     * Get the relationship name.
     */
    public function relationshipName(): string
    {
        return $this->hasOneRelationship;
    }

    /**
     * Get the relationship type.
     */
    public function relationshipType(): string
    {
        return 'hasOne';
    }

    /**
     * Determine if the field should be displayed for the given request.
     *
     * @return bool
     */
    #[\Override]
    public function authorize(Request $request)
    {
        return \call_user_func(
            [$this->resourceClass, 'authorizedToViewAny'], $request
        ) && parent::authorize($request);
    }

    /**
     * Determine if the field should be for the given request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     */
    public function authorizedToRelate(Request $request): bool
    {
        return $request->findResource()->authorizedToAdd($request, $this->resourceClass::newModel())
            && $this->resourceClass::authorizedToCreate($request);
    }

    /**
     * Resolve the field's value.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object  $resource
     */
    #[\Override]
    public function resolve($resource, ?string $attribute = null): void
    {
        $value = null;

        if ($resource->relationLoaded($this->attribute)) {
            $value = $resource->getRelation($this->attribute);
        }

        if (! $value) {
            $value = $resource->{$this->attribute}()->withoutGlobalScopes()->getResults();
        }

        if ($value) {
            $this->alreadyFilledWhen(static fn () => optional($value)->exists);

            $this->hasOneResource = new $this->resourceClass($value);

            $this->hasOneId = optional(ID::forResource($this->hasOneResource))->value ?? $value->getKey();

            $this->value = $this->hasOneId;
        }
    }

    /**
     * Set the displayable singular label of the resource.
     *
     * @return $this
     */
    public function singularLabel(Stringable|string $singularLabel)
    {
        $this->singularLabel = $singularLabel;

        return $this;
    }

    /**
     * Make current field behaves as panel.
     */
    public function asPanel(): Panel
    {
        return Panel::make($this->name, [$this])
            ->withMeta([
                'prefixComponent' => true,
            ])
            ->help($this->getHelpText())
            ->withComponent('relationship-panel');
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return with(app(NovaRequest::class), function ($request) {
            if (! \is_null($this->requiredCallback)) {
                $this->nullable = ! with($this->requiredCallback, static function ($callback) use ($request) {
                    return $callback === true || (\is_callable($callback) && \call_user_func($callback, $request));
                });
            }

            return array_merge([
                'resourceName' => $this->resourceName,
                'hasOneRelationship' => $this->hasOneRelationship,
                'relationshipType' => $this->relationshipType(),
                'relationId' => $this->hasOneId,
                'hasOneId' => $this->hasOneId,
                'relatable' => true,
                'singularLabel' => $this->singularLabel,
                'alreadyFilled' => $this->alreadyFilled($request),
                'authorizedToView' => optional($this->hasOneResource)->authorizedToView($request) ?? true,
                'authorizedToCreate' => $this->ofManyRelationship === true ? false : $this->authorizedToRelate($request),
                'createButtonLabel' => $this->resourceClass::createButtonLabel(),
                'from' => array_filter([
                    'viaResource' => $request->resource,
                    'viaResourceId' => $request->resourceId,
                    'viaRelationship' => $request->viaRelationship ?? $this->attribute,
                ]),
            ], parent::jsonSerialize());
        });
    }

    /**
     * Determine if the field is required.
     */
    #[\Override]
    public function isRequired(NovaRequest $request): bool
    {
        if (\is_null($this->isRequired)) {
            $this->isRequired = parent::isRequired($request);
        }

        $this->nullable = ! $this->isRequired;

        return $this->isRequired;
    }

    /**
     * Set the Closure used to determine if the HasOne field has already been filled.
     *
     * @param  callable(\Laravel\Nova\Http\Requests\NovaRequest):bool  $callback
     * @return $this
     */
    public function alreadyFilledWhen(callable $callback)
    {
        $this->filledCallback = $callback;

        return $this;
    }

    /**
     * Determine if the HasOne field has alreaady been filled.
     */
    public function alreadyFilled(NovaRequest $request): bool
    {
        /** @phpstan-ignore nullCoalesce.expr */
        return \call_user_func($this->filledCallback, $request) ?? false;
    }

    /**
     * Check showing on index.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|object  $resource
     */
    #[\Override]
    public function isShownOnIndex(NovaRequest $request, $resource): bool
    {
        return false;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     * @return (callable():(void))|null
     */
    #[\Override]
    public function fillInto(NovaRequest $request, $model, string $attribute, ?string $requestAttribute = null): ?callable
    {
        $resourceClass = $this->resourceClass;
        $relation = $model->loadMissing($this->hasOneRelationship)->getRelation($this->hasOneRelationship) ?? $resourceClass::newModel();

        $editMode = model_exists($relation) ? 'update' : 'create';

        $filled = collect($request->{$attribute} ?? [])->filter()->isNotEmpty();

        if (
            $this->ofManyRelationship === true
            || ($this->nullable && ! $filled && $editMode === 'create')
        ) {
            return null;
        }

        $resourceClass = $this->resourceClass;
        $resource = $resourceClass::make($relation);

        $callbacks = $resource->availableFields($request)
            ->when($editMode === 'create', static function (FieldCollection $fields) use ($request, $relation) {
                return $fields->onlyCreateFields($request, $relation);
            })
            ->when($editMode === 'update', static function (FieldCollection $fields) use ($request, $relation) {
                return $fields->onlyUpdateFields($request, $relation);
            })
            ->withoutReadonly($request)
            ->withoutUnfillable()
            ->map(static function (Field $field) use ($request, $relation, $attribute) {
                return $field->fillInto($request, $relation, $field->attribute, "{$attribute}.{$field->attribute}");
            });

        if ($editMode === 'create') {
            $callbacks->prepend(function () use ($request, $relation, $model) {
                $model->{$this->hasOneRelationship}()->save($relation);

                Nova::usingActionEvent(static function ($actionEvent) use ($request, $relation) {
                    $actionEvent->forResourceCreate(Nova::user($request), $relation)->save();
                });
            });
        } else {
            Nova::usingActionEvent(static function ($actionEvent) use ($request, $relation) {
                $actionEvent->forResourceUpdate(Nova::user($request), $relation)->save();
            });

            $relation->save();
        }

        $model->setRelation($this->hasOneRelationship, $relation);

        return static function () use ($callbacks) {
            $callbacks->filter(static fn ($callback) => \is_callable($callback))
                ->each->__invoke();
        };
    }

    /**
     * Get the creation rules for this field.
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rule|\Illuminate\Contracts\Validation\Rule|callable>>
     */
    public function getCreationRules(NovaRequest $request): array
    {
        return $this->getAvailableValidationRules($request);
    }

    /**
     * Get the update rules for this field.
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rule|\Illuminate\Contracts\Validation\Rule|callable>>
     */
    public function getUpdateRules(NovaRequest $request): array
    {
        return $this->getAvailableValidationRules($request);
    }

    /**
     * Get the available rules for this field.
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rule|\Illuminate\Contracts\Validation\Rule|callable>>
     */
    protected function getAvailableValidationRules(NovaRequest $request): array
    {
        $model = $request->findModel();
        $resourceClass = $this->resourceClass;

        $relation = method_exists($model, $this->hasOneRelationship)
            ? $model->loadMissing($this->hasOneRelationship)->getRelation($this->hasOneRelationship) ?? $resourceClass::newModel()
            : null;

        if (\is_null($relation)) {
            return [];
        }

        $resource = $resourceClass::make($relation);

        return model_exists($relation)
            ? $this->getResourceUpdateRules($request, $resource)
            : $this->getResourceCreationRules($request, $resource);
    }

    /**
     * Get the creation rules for this field.
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rule|\Illuminate\Contracts\Validation\Rule|callable>>
     */
    public function getResourceCreationRules(NovaRequest $request, Resource $resource): array
    {
        $replacements = Util::dependentRules($this->attribute);

        return $resource->creationFields($request)
            ->reject(static fn ($field) => $field instanceof BelongsTo && $field->resourceClass == Nova::resourceForKey($request->resource))
            ->applyDependsOn($request)
            ->mapWithKeys(static fn ($field) => $field->getCreationRules($request))
            ->mapWithKeys(function ($field, $attribute) use ($replacements) {
                if ($this->nullable === true) {
                    $field[] = 'sometimes';
                }

                return ["{$this->attribute}.{$attribute}" => collect($field)->transform(function ($rule) use ($replacements) {
                    if (empty($replacements)) {
                        return $rule;
                    }

                    return \is_string($rule)
                            ? str_replace(array_keys($replacements), array_values($replacements), $rule)
                            : $rule;
                })->all()];
            })
            ->prepend(['array', $this->nullable === true ? 'nullable' : 'required'], $this->attribute)
            ->all();
    }

    /**
     * Get the update rules for this resource fields.
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rule|\Illuminate\Contracts\Validation\Rule|callable>>
     */
    public function getResourceUpdateRules(NovaRequest $request, Resource $resource): array
    {
        $replacements = collect([
            '{{resourceId}}' => str_replace(['\'', '"', ',', '\\'], '', $resource->model()->getKey() ?? ''),
        ])->merge(
            Util::dependentRules($this->attribute),
        )->filter()->all();

        return $resource->updateFields($request)
            ->reject($this->rejectRecursiveRelatedResourceFields($request))
            ->applyDependsOn($request)
            ->mapWithKeys(static fn ($field) => $field->getUpdateRules($request))
            ->mapWithKeys(function ($field, $attribute) use ($replacements) {
                if ($this->nullable === true) {
                    $field[] = 'sometimes';
                }

                return ["{$this->attribute}.{$attribute}" => collect($field)->transform(function ($rule) use ($replacements) {
                    if (empty($replacements)) {
                        return $rule;
                    }

                    return \is_string($rule)
                            ? str_replace(array_keys($replacements), array_values($replacements), $rule)
                            : $rule;
                })->all()];
            })
            ->prepend(['array', $this->nullable === true ? 'nullable' : 'required'], $this->attribute)
            ->all();
    }

    /**
     * Get the validation attribute names for the field.
     *
     * @return array<string, string>
     */
    public function getValidationAttributeNames(NovaRequest $request): array
    {
        $resourceClass = $this->resourceClass;
        $resource = $resourceClass::make($resourceClass::newModel());

        return $resource->updateFields($request)
            ->reject($this->rejectRecursiveRelatedResourceFields($request))
            ->reject(static fn ($field) => empty($field->name))
            ->mapWithKeys(fn ($field) => ["{$this->attribute}.{$field->attribute}" => $field->name])
            ->all();
    }

    /**
     * Determine if the relationship is a of-many relationship.
     */
    public function ofManyRelationship(): bool
    {
        return $this->ofManyRelationship;
    }

    /**
     * Check for showing when creating.
     */
    #[\Override]
    public function isShownOnCreation(NovaRequest $request): bool
    {
        return \call_user_func($this->rejectRecursiveRelatedResourceFields($request), $this) === false
            && parent::isShownOnCreation($request);
    }

    /**
     * Check for showing when updating.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|object  $resource
     */
    #[\Override]
    public function isShownOnUpdate(NovaRequest $request, $resource): bool
    {
        return \call_user_func($this->rejectRecursiveRelatedResourceFields($request), $this) === false
            && parent::isShownOnUpdate($request, $resource);
    }

    /**
     * Reject recursive related resource fields.
     */
    protected function rejectRecursiveRelatedResourceFields(NovaRequest $request): callable
    {
        return function ($field) use ($request) {
            if (! $field instanceof RelatableField) {
                return false;
            }

            $relatedResource = $field->resourceName == $request->resource;

            return ($this->relationshipType() === 'hasOne' && $field instanceof BelongsTo && $relatedResource) ||
                ($this->relationshipType() === 'morphOne' && $field instanceof MorphTo && $relatedResource);
        };
    }

    /**
     * Show the field in the modal preview.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool  $callback
     * @return never
     *
     * @throws \Laravel\Nova\Exceptions\HelperNotSupported
     */
    public function showOnPreview(callable|bool $callback = true)
    {
        throw NovaException::helperNotSupported(__METHOD__, __CLASS__);
    }

    /**
     * Specify that the element should only be shown on the preview modal.
     *
     * @return never
     *
     * @throws \Laravel\Nova\Exceptions\HelperNotSupported
     */
    public function onlyOnPreview()
    {
        throw NovaException::helperNotSupported(__METHOD__, __CLASS__);
    }
}
