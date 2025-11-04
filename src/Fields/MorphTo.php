<?php

namespace Laravel\Nova\Fields;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Laravel\Nova\Contracts\FilterableField;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Fields\Filters\MorphToFilter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Http\Requests\ResourceIndexRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;
use Laravel\Nova\Rules\Relatable;

use function Orchestra\Sidekick\Http\safe_int;
use function Orchestra\Sidekick\is_safe_callable;

/**
 * @method static static make(\Stringable|string $name, string|null $attribute = null)
 */
class MorphTo extends Field implements FilterableField, RelatableField
{
    use AssociatableRelation;
    use DeterminesIfCreateRelationCanBeShown;
    use EloquentFilterable;
    use Peekable;
    use ResolvesReverseRelation;
    use Searchable;
    use SupportsDependentFields;
    use SupportsWithTrashedRelatables;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'morph-to-field';

    /**
     * The class name of the related resource.
     *
     * @var class-string<\Laravel\Nova\Resource>|null
     */
    public $resourceClass = null;

    /**
     * The URI key of the related resource.
     *
     * @var string|null
     */
    public $resourceName = null;

    /**
     * The resolved MorphTo Resource.
     *
     * @var \Laravel\Nova\Resource|null
     */
    public $morphToResource = null;

    /**
     * The name of the Eloquent "morph to" relationship.
     *
     * @var string
     */
    public $morphToRelationship;

    /**
     * The key of the related Eloquent model.
     *
     * @var string|int|null
     */
    public $morphToId = null;

    /**
     * The type of the related Eloquent model.
     *
     * @var string|null
     */
    public $morphToType = null;

    /**
     * The types of resources that may be polymorphically related to this resource.
     *
     * @var array<array-key, array<string, mixed>>
     */
    public $morphToTypes = [];

    /**
     * The column that should be displayed for the field.
     *
     * @var callable|array<class-string<\Laravel\Nova\Resource>, callable>|string
     */
    public $display;

    /**
     * Indicates if the related resource can be viewed.
     *
     * @var bool|null
     */
    public $viewable = null;

    /**
     * The attribute that is the inverse of this relationship.
     *
     * @var string|null
     */
    public $inverse = null;

    /**
     * The default related class value for the field.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(class-string<\Laravel\Nova\Resource>))|class-string<\Laravel\Nova\Resource>|null
     */
    public $defaultResourceCallable;

    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     */
    public function __construct($name, ?string $attribute = null)
    {
        parent::__construct($name, $attribute);

        $this->morphToRelationship = $this->attribute = $attribute ?? ResourceRelationshipGuesser::guessRelation($name);
    }

    /**
     * Get the relationship name.
     */
    public function relationshipName(): string
    {
        return $this->morphToRelationship;
    }

    /**
     * Get the relationship type.
     */
    public function relationshipType(): string
    {
        return 'morphTo';
    }

    /**
     * Determine if the field should be displayed for the given request.
     *
     * @param  \Illuminate\Http\Request&\Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return bool
     */
    #[\Override]
    public function authorize(Request $request)
    {
        if (! $this->isNotRedundant($request)) {
            return false;
        }

        return parent::authorize($request);
    }

    /**
     * Determine if the field is not redundant.
     *
     * See: Explanation on belongsTo field.
     */
    public function isNotRedundant(NovaRequest $request): bool
    {
        return ! $request instanceof ResourceIndexRequest || ! $this->isReverseRelation($request);
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

        [$this->morphToId, $this->morphToType] = [
            optional($value)->getKey(),
            $this->resolveMorphType($resource),
        ];

        if ($resourceClass = $this->resolveResourceClass($value)) {
            $this->resourceName = $resourceClass::uriKey();
        }

        if ($value) {
            if (! \is_string($this->resourceClass)) {
                $this->morphToType = $value->getMorphClass();
                $this->value = (string) $value->getKey();

                if ($this->value != $value->getKey()) {
                    $this->morphToId = (string) $this->morphToId;
                }

                $this->viewable = false;
            } else {
                $this->morphToResource = new $this->resourceClass($value);

                $this->morphToId = safe_int($this->morphToId);

                $resource = Nova::newResourceFromModel($value);

                $this->value = $this->formatDisplayValue($resource, $resource::class);

                $this->viewable = ($this->viewable ?? true) && $this->morphToResource->authorizedToView(app(NovaRequest::class));
            }
        }
    }

    /**
     * Resolve the field's value for display.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object  $resource
     */
    #[\Override]
    public function resolveForDisplay($resource, ?string $attribute = null): void
    {
        $this->resolve($resource, $attribute);
    }

    /**
     * Resolve dependent field value.
     */
    public function resolveDependentValue(NovaRequest $request): mixed
    {
        return $this->morphToId ?? $this->resolveDefaultValue($request);
    }

    /**
     * Resolve the current resource key for the resource's morph type.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model  $resource
     */
    protected function resolveMorphType($resource): ?string
    {
        if (! $type = optional($resource->{$this->attribute}())->getMorphType()) {
            return null;
        }

        $value = $resource->{$type};

        if ($morphResource = Nova::resourceForModel(Relation::getMorphedModel($value) ?? $value)) {
            return $morphResource::uriKey();
        }

        return null;
    }

    /**
     * Resolve the resource class for the field.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    protected function resolveResourceClass($model): ?string
    {
        return $this->resourceClass = Nova::resourceForModel($model);
    }

    /**
     * Get the validation rules for this field.
     */
    public function getRules(NovaRequest $request): array
    {
        $possibleTypes = collect($this->morphToTypes)->map->value->values();

        return array_merge_recursive(parent::getRules($request), [
            $this->attribute.'_type' => [$this->nullable ? 'nullable' : 'required', 'in:'.$possibleTypes->implode(',')],
            $this->attribute => array_filter([$this->nullable ? 'nullable' : 'required', $this->getRelatableRule($request)]),
        ]);
    }

    /**
     * Get the validation rule to verify that the selected model is relatable.
     */
    protected function getRelatableRule(NovaRequest $request): ?Relatable
    {
        if ($relatedResource = Nova::resourceForKey($request->{$this->attribute.'_type'})) {
            return new Relatable($request, $this->buildAssociatableQuery(
                $request, $relatedResource, $request->{$this->attribute.'_trashed'} === 'true'
            )->toBase(), $this);
        }

        return null;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     * @return void
     */
    #[\Override]
    public function fill(NovaRequest $request, object $model)
    {
        $instance = Nova::modelInstanceForKey($request->{$this->attribute.'_type'});

        $relationship = $model->{$this->attribute}();

        $morphType = $relationship->getMorphType();

        $model->setAttribute(
            $morphType,
            ! \is_null($instance) ? $this->getMorphAliasForClass($instance::class) : null
        );

        $foreignKey = $this->getRelationForeignKeyName($relationship);

        parent::fillInto($request, $model, $foreignKey);

        if (\is_null($model->getAttribute($morphType))) {
            $model->setAttribute($foreignKey, null);
        }

        if ($model->isDirty([$morphType, $foreignKey])) {
            $model->unsetRelation($this->attribute);
        }
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     */
    #[\Override]
    public function fillForAction(NovaRequest $request, object $model): void
    {
        if ($request->exists($this->attribute)) {
            $value = $request[$this->attribute];

            $instance = Nova::modelInstanceForKey($request->{$this->attribute.'_type'});

            $model->{$this->attribute} = $instance->query()->find($value);
        }
    }

    /**
     * Get the morph type alias for the given class.
     */
    protected function getMorphAliasForClass(string $class): string
    {
        foreach (Relation::$morphMap as $alias => $model) {
            if ($model == $class) {
                return $alias;
            }
        }

        return $class;
    }

    /**
     * Format the given morphable resource.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model  $resource
     * @param  class-string<\Laravel\Nova\Resource>  $relatedResource
     */
    public function formatMorphableResource(NovaRequest $request, object $resource, string $relatedResource): array
    {
        if (! $resource instanceof Resource) {
            $resource = Nova::newResourceFromModel($resource);
        }

        return array_filter([
            'avatar' => $resource->resolveAvatarUrl($request),
            'display' => $this->formatDisplayValue($resource, $relatedResource),
            'subtitle' => $resource->subtitle(),
            'value' => safe_int($resource->getKey()),
        ]);
    }

    /**
     * Format the associatable display value.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $relatedResource
     */
    protected function formatDisplayValue(Resource $resource, string $relatedResource): string
    {
        if ($display = $this->displayFor($relatedResource)) {
            return \call_user_func($display, $resource);
        }

        return (string) $resource->title();
    }

    /**
     * Set the types of resources that may be related to the resource.
     *
     * @param  array<int, class-string<\Laravel\Nova\Resource>>|array<class-string<\Laravel\Nova\Resource>, string>  $types
     * @return $this
     */
    public function types(array $types)
    {
        $this->morphToTypes = collect($types)
            ->map(static fn ($display, $key) => [ // @phpstan-ignore argument.unresolvableType
                'type' => is_numeric($key) ? $display : $key,
                'singularLabel' => is_numeric($key) ? $display::singularLabel() : $key::singularLabel(),
                'display' => (\is_string($display) && is_numeric($key)) ? $display::singularLabel() : $display,
                'value' => is_numeric($key) ? $display::uriKey() : $key::uriKey(),
            ])->values()->all();

        return $this;
    }

    /**
     * Set the column that should be displayed for the field.
     *
     * @param  callable|array<class-string<\Laravel\Nova\Resource>, callable>|string  $display
     * @return $this
     */
    public function display($display)
    {
        if (\is_array($display)) {
            $this->display = collect($display)->mapWithKeys(
                fn ($display, $type) => [$type => $this->ensureDisplayerIsCallable($display)]
            )->all();
        } else {
            $this->display = $this->ensureDisplayerIsCallable($display);
        }

        return $this;
    }

    /**
     * Ensure the given displayer is a callable.
     *
     * @param  callable|string  $display
     */
    protected function ensureDisplayerIsCallable($display): callable
    {
        return is_safe_callable($display)
            ? $display
            : fn ($resource) => $resource->{$display};
    }

    /**
     * Get the column that should be displayed for a given type.
     */
    public function displayFor(string $type): ?callable
    {
        if (\is_array($this->display) && $type) {
            return $this->display[$type] ?? null;
        }

        return $this->display;
    }

    /**
     * Specify if the related resource can be viewed.
     *
     * @param  bool  $value
     * @return $this
     */
    public function viewable($value = true)
    {
        $this->viewable = $value;

        return $this;
    }

    /**
     * Set the attribute name of the inverse of the relationship.
     *
     * @param  string  $inverse
     * @return $this
     */
    public function inverse($inverse)
    {
        $this->inverse = $inverse;

        return $this;
    }

    /**
     * Set the default relation resource class to be selected.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(class-string<\Laravel\Nova\Resource>))|class-string<\Laravel\Nova\Resource>  $resourceClass
     * @return $this
     */
    public function defaultResource($resourceClass)
    {
        $this->defaultResourceCallable = $resourceClass;

        return $this;
    }

    /**
     * Resolve the default resource class for the field.
     *
     * @return string|void
     */
    protected function resolveDefaultResource(NovaRequest $request)
    {
        if ($request->isCreateOrAttachRequest() || $request->isResourceIndexRequest() || $request->isActionRequest()) {
            if (\is_null($this->value) && is_safe_callable($this->defaultResourceCallable)) {
                $class = \call_user_func($this->defaultResourceCallable, $request);
            } else {
                $class = $this->defaultResourceCallable;
            }

            if (! empty($class) && class_exists($class)) {
                return $class::uriKey();
            }
        }
    }

    /**
     * Make the field filter.
     *
     * @return \Laravel\Nova\Fields\Filters\Filter|null
     */
    protected function makeFilter(NovaRequest $request)
    {
        return new MorphToFilter($this);
    }

    /**
     * Define filterable attribute.
     *
     * @return string
     */
    protected function filterableAttribute(NovaRequest $request)
    {
        return $this->morphToRelationship;
    }

    /**
     * Define the default filterable callback.
     *
     * @return callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Contracts\Database\Eloquent\Builder, mixed, string):void
     */
    protected function defaultFilterableCallback()
    {
        $morphToTypes = collect($this->morphToTypes)
                            ->pluck('type')
                            ->mapWithKeys(static fn ($type) => [$type => $type::newModel()->getMorphClass()])
                            ->all();

        return function (NovaRequest $request, $query, $value, $attribute) use ($morphToTypes) {
            $query->whereHasMorph(
                $attribute,
                ! empty($value) && isset($morphToTypes[$value]) ? $morphToTypes[$value] : $morphToTypes
            );
        };
    }

    /**
     * Prepare the field for JSON serialization.
     */
    public function serializeForFilter(): array
    {
        return transform($this->jsonSerialize(), static fn ($field) => [
            'resourceName' => $field['resourceName'],
            'morphToTypes' => $field['morphToTypes'],
            'uniqueKey' => $field['uniqueKey'],
            'relationshipType' => $field['relationshipType'],
        ]);
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $resourceClass = $this->resourceClass;

        return with(app(NovaRequest::class), function ($request) use ($resourceClass) {
            $viewable = ! \is_null($this->viewable)
                    ? $this->viewable
                    : (! \is_null($resourceClass) ? $resourceClass::authorizedToViewAny($request) : true);

            return array_merge([
                'debounce' => $this->debounce,
                'morphToRelationship' => $this->morphToRelationship,
                'relationshipType' => $this->relationshipType(),
                'morphToType' => $this->morphToType,
                'morphToId' => $this->morphToId,
                'morphToTypes' => $this->morphToTypes,
                'peekable' => $this->isPeekable($request),
                'hasFieldsToPeekAt' => $this->hasFieldsToPeekAt($request),
                'resourceLabel' => $resourceClass ? $resourceClass::singularLabel() : null,
                'resourceName' => $this->resourceName,
                'reverse' => $this->isReverseRelation($request),
                'searchable' => $this->isSearchable($request),
                'withSubtitles' => $this->withSubtitles,
                'showCreateRelationButton' => $this->createRelationShouldBeShown($request),
                'displaysWithTrashed' => $this->displaysWithTrashed,
                'viewable' => $viewable,
                'defaultResource' => $this->resolveDefaultResource($request),
            ], parent::jsonSerialize());
        });
    }
}
