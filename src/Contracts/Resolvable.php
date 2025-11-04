<?php

namespace Laravel\Nova\Contracts;

/**
 * @property bool $pivot
 * @property string|null $pivotAccessor
 * @property \Illuminate\Database\Eloquent\Relations\MorphToMany|\Illuminate\Database\Eloquent\Relations\BelongsToMany|null $pivotRelation
 */
interface Resolvable
{
    /**
     * Resolve the element's value.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|object|array  $resource
     */
    public function resolve($resource, ?string $attribute = null): void;

    /**
     * Resolve the field's value for display.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|object|array  $resource
     */
    public function resolveForDisplay($resource, ?string $attribute = null): void;
}
