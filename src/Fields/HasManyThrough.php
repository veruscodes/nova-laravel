<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Contracts\ListableField;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Panel;

/**
 * @method static static make(\Stringable|string $name, string|null $attribute = null, string|null $resource = null)
 */
class HasManyThrough extends HasMany implements ListableField, RelatableField
{
    use Collapsable;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'has-many-through-field';

    /**
     * The name of the Eloquent "has many through" relationship.
     *
     * @var string
     */
    public $hasManyThroughRelationship;

    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     * @param  class-string<\Laravel\Nova\Resource>|null  $resource
     */
    public function __construct($name, ?string $attribute = null, ?string $resource = null)
    {
        parent::__construct($name, $attribute, $resource);

        $this->hasManyThroughRelationship = $this->attribute = $attribute ?? ResourceRelationshipGuesser::guessRelation($name);
    }

    /**
     * Get the relationship name.
     */
    public function relationshipName(): string
    {
        return $this->hasManyThroughRelationship;
    }

    /**
     * Get the relationship type.
     */
    public function relationshipType(): string
    {
        return 'hasManyThrough';
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
        return array_merge([
            'collapsable' => $this->collapsable,
            'collapsedByDefault' => $this->collapsedByDefault,
            'hasManyThroughRelationship' => $this->hasManyThroughRelationship,
            'relatable' => true,
            'perPageOptions' => $this->resourceClass::perPageViaRelationshipOptions(),
            'resourceName' => $this->resourceName,
            'singularLabel' => $this->singularLabel ?? $this->resourceClass::singularLabel(),
        ], parent::jsonSerialize());
    }
}
