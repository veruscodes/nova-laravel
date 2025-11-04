<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\TrashedStatus;

trait QueriesResources
{
    use DecodesFilters;

    /**
     * Transform the request into a query.
     */
    public function toQuery(): Builder
    {
        $resourceClass = $this->resource();

        return $resourceClass::buildIndexQuery(
            $this, $this->newQuery(), $this->search,
            $this->filters()->all(), $this->orderings(), $this->trashed()
        );
    }

    /**
     * Get a new query builder for the underlying model.
     */
    public function newQuery(): Builder
    {
        if (! $this->viaRelationship()) {
            /** @return \Illuminate\Database\Eloquent\Builder */
            return $this->model()->newQuery();
        }

        abort_unless($this->newViaResource()->hasRelatableFieldOrRelationship($this, $this->viaRelationship), 409);

        /** @return \Illuminate\Database\Eloquent\Relations\Relation */
        return forward_static_call([$this->viaResource(), 'newModel'])
            ->newQueryWithoutScopes()->findOrFail(
                $this->viaResourceId
            )->{$this->viaRelationship}();
    }

    /**
     * Get a new query builder for the underlying model.
     */
    public function newQueryWithoutScopes(): Builder
    {
        if (! $this->viaRelationship()) {
            /** @return \Illuminate\Database\Eloquent\Builder */
            return $this->model()->newQueryWithoutScopes();
        }

        abort_unless($this->newViaResource()->hasRelatableFieldOrRelationship($this, $this->viaRelationship), 409);

        /** @return \Illuminate\Database\Eloquent\Relations\Relation */
        return forward_static_call([$this->viaResource(), 'newModel'])
            ->newQueryWithoutScopes()->findOrFail(
                $this->viaResourceId
            )->{$this->viaRelationship}()->withoutGlobalScopes();
    }

    /**
     * Get the orderings for the request.
     */
    public function orderings(): array
    {
        return ! empty($this->orderBy)
            ? [$this->orderBy => $this->orderByDirection]
            : [];
    }

    /**
     * Get the trashed status of the request.
     */
    abstract public function trashed(): TrashedStatus;
}
