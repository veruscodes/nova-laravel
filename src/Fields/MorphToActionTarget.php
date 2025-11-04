<?php

namespace Laravel\Nova\Fields;

use Illuminate\Database\Eloquent\Relations\Relation;
use Laravel\Nova\Http\Requests\NovaRequest;

class MorphToActionTarget extends MorphTo
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'morph-to-action-target-field';

    /**
     * Determine if the field is not redundant.
     */
    #[\Override]
    public function isNotRedundant(NovaRequest $request): bool
    {
        return true;
    }

    /**
     * Resolve the field's value.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object  $resource
     */
    #[\Override]
    public function resolve($resource, ?string $attribute = null): void
    {
        parent::resolve($resource, $attribute);

        if (empty($this->value)) {
            $morphToType = $resource->getAttribute("{$this->attribute}_type");
            $morphToId = $resource->getAttribute("{$this->attribute}_id");

            $this->morphToType = Relation::getMorphedModel($morphToType) ?? $morphToType;
            $this->morphToId = $this->value = (string) $morphToId;
            $this->viewable = false;
        }
    }
}
