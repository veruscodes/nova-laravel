<?php

namespace Laravel\Nova\Fields;

use Closure;
use Laravel\Nova\Contracts\PivotableField;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;

use function Orchestra\Sidekick\Http\safe_int;

/**
 * @method static static make(\Stringable|string $name, string|null $attribute = null, string|null $resource = null)
 */
class Tag extends Field implements PivotableField
{
    use AttachableRelation;
    use DeterminesIfCreateRelationCanBeShown;
    use FormatsRelatableDisplayValues;
    use ManyToManyCreationRules;
    use Searchable;
    use SupportsDependentFields;

    public const LIST_STYLE = 'list';

    public const GROUP_STYLE = 'group';

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'tag-field';

    /**
     * The text alignment for the field's text in tables.
     *
     * @var string
     */
    public $textAlign = 'center';

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
     * The name of the Eloquent "belongs to many" relationship.
     *
     * @var string
     */
    public $manyToManyRelationship;

    /**
     * The visual style to use when display the tags.
     *
     * @var string
     */
    public $style = 'group';

    /**
     * Indicates if Nova should show a preview modal for the tag.
     *
     * @var bool
     */
    public $withPreview = false;

    /**
     * Indicates if Nova should preload the tags.
     *
     * @var bool
     */
    public $preload = false;

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
        $this->manyToManyRelationship = $this->attribute = $attribute ?? ResourceRelationshipGuesser::guessRelation($name);

        $this->allowDuplicateRelations();
    }

    /**
     * Get the relationship name.
     */
    public function relationshipName(): string
    {
        return $this->manyToManyRelationship;
    }

    /**
     * Get the relationship type.
     */
    public function relationshipType(): string
    {
        return 'belongsToMany';
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     */
    #[\Override]
    protected function fillAttributeFromRequest(NovaRequest $request, string $requestAttribute, object $model, string $attribute): Closure
    {
        return function () use ($model, $attribute, $request, $requestAttribute) {
            $model->{$attribute}()->sync(
                $this->prepareRelations($request, $requestAttribute)
            );
        };
    }

    /**
     * Prepare relation values from request.
     *
     * @return array<int, string>
     */
    protected function prepareRelations(NovaRequest $request, string $requestAttribute): array
    {
        if (! $request->filled($requestAttribute)) {
            return [];
        }

        return collect(json_decode($request[$requestAttribute], true))
            ->pluck('value')
            ->filter()
            ->all();
    }

    /**
     * Resolve the given attribute from the given resource.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object  $resource
     */
    protected function resolveAttribute($resource, string $attribute): array
    {
        return $resource->{$attribute}
            ->map(function ($model) {
                return $this->transformResult(
                    app(NovaRequest::class), Nova::newResourceFromModel($model)
                );
            })->values()->all();
    }

    /**
     * Set the field to display as a list of rows.
     *
     * @return $this
     */
    public function displayAsList()
    {
        $this->style = static::LIST_STYLE;

        return $this;
    }

    /**
     * Set the field to display a preview modal when clicking the tag.
     *
     * @return $this
     */
    public function withPreview()
    {
        $this->withPreview = true;

        return $this;
    }

    /**
     * Preload all options for the field on load.
     *
     * @return $this
     */
    public function preload()
    {
        $this->preload = true;

        return $this;
    }

    /**
     * Transform the result from resource.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model  $resource
     */
    protected function transformResult(NovaRequest $request, $resource): array
    {
        if (! $resource instanceof Resource) {
            $resource = Nova::newResourceFromModel($resource);
        }

        return array_filter([
            'avatar' => $resource->resolveAvatarUrl($request),
            'display' => (string) $resource->title(),
            'subtitle' => $resource->subtitle(),
            'value' => safe_int($resource->getKey()),
        ]);
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return with(app(NovaRequest::class), function ($request) {
            return array_merge([
                'preload' => $this->preload,
                'style' => $this->style,
                'belongsToManyRelationship' => $this->manyToManyRelationship,
                'resourceName' => $this->resourceName,
                'withSubtitles' => $this->withSubtitles,
                'showCreateRelationButton' => $this->createRelationShouldBeShown($request),
                'singularLabel' => $this->singularLabel ?? $this->resourceClass::singularLabel(),
                'validationKey' => $this->validationKey(),
                'withPreview' => $this->withPreview,
            ], parent::jsonSerialize());
        });
    }
}
