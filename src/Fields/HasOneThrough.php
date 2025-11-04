<?php

namespace Laravel\Nova\Fields;

use Illuminate\Http\Request;
use Laravel\Nova\Contracts\BehavesAsPanel;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Panel;
use Laravel\Nova\Resource;
use Stringable;

use function Orchestra\Sidekick\Eloquent\model_exists;

/**
 * @method static static make(\Stringable|string $name, string|null $attribute = null, string|null $resource = null)
 */
class HasOneThrough extends Field implements BehavesAsPanel, RelatableField
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'has-one-through-field';

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
     * The resolved HasOneThrough Resource.
     *
     * @var \Laravel\Nova\Resource|null
     */
    public $hasOneThroughResource = null;

    /**
     * The name of the Eloquent "has one through" relationship.
     *
     * @var string
     */
    public $hasOneThroughRelationship;

    /**
     * The key of the related Eloquent model.
     *
     * @var string|int|null
     */
    public $hasOneThroughId = null;

    /**
     * The callback used to determine if the HasOne field has already been filled.
     *
     * @var callable(\Laravel\Nova\Http\Requests\NovaRequest):bool
     */
    public $filledCallback;

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
        $this->hasOneThroughRelationship = $this->attribute = $attribute ?? ResourceRelationshipGuesser::guessRelation($name);
        $this->singularLabel = $resource::singularLabel();

        $this->alreadyFilledWhen(function ($request) {
            $parentResource = Nova::resourceForKey($request->viaResource);

            if ($parentResource && filled($request->viaResourceId)) {
                $parent = $parentResource::newModel()->find($request->viaResourceId);

                return model_exists($parent->{$this->attribute});
            }

            return false;
        });
    }

    /**
     * Get the relationship name.
     */
    public function relationshipName(): string
    {
        return $this->hasOneThroughRelationship;
    }

    /**
     * Get the relationship type.
     */
    public function relationshipType(): string
    {
        return 'hasOneThrough';
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
     * Resolve the field's value.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model  $resource
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

            $this->hasOneThroughResource = new $this->resourceClass($value);

            $this->hasOneThroughId = optional(ID::forResource($this->hasOneThroughResource))->value ?? $value->getKey();

            $this->value = $this->hasOneThroughId;
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
            ])->withComponent('relationship-panel');
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
            return array_merge([
                'resourceName' => $this->resourceName,
                'hasOneThroughRelationship' => $this->hasOneThroughRelationship,
                'relationId' => $this->hasOneThroughId,
                'hasOneThroughId' => $this->hasOneThroughId,
                'authorizedToView' => optional($this->hasOneThroughResource)->authorizedToView($request) ?? true,
                'relationshipType' => $this->relationshipType(),
                'relatable' => true,
                'singularLabel' => $this->singularLabel,
                'alreadyFilled' => $this->alreadyFilled($request),
            ], parent::jsonSerialize());
        });
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
    public function isShownOnIndex(NovaRequest $request, $resource): bool
    {
        return false;
    }
}
