<?php

namespace Laravel\Nova\Contracts;

use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * @mixin \Laravel\Nova\Fields\Field
 *
 * @property bool $allowDuplicateRelations
 * @property string $manyToManyRelationship
 */
interface PivotableField extends RelatableField
{
    /**
     * Build an attachable query for the field.
     */
    public function buildAttachableQuery(NovaRequest $request, bool $withTrashed = false): QueryBuilder;

    /**
     * Format the given attachable resource.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model  $resource
     */
    public function formatAttachableResource(NovaRequest $request, $resource): array;

    /**
     * Determine if the display values should be automatically sorted when rendering attachable relation.
     */
    public function shouldReorderAttachableValues(NovaRequest $request): bool;
}
