<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Laravel\Nova\Contracts\RelatableField;

use function Orchestra\Sidekick\Eloquent\table_name;

/**
 * @property-read string|null $orderBy
 * @property-read string|null $orderByDirection
 */
class LensRequest extends NovaRequest
{
    use DecodesFilters;
    use InteractsWithLenses;

    /**
     * Whether to include the table order prefix.
     */
    protected bool $tableOrderPrefix = true;

    /**
     * Apply the specified filters to the given query.
     */
    public function withFilters(Builder $query): Builder
    {
        return $this->filter($query);
    }

    /**
     * Apply the specified filters to the given query.
     */
    public function filter(Builder $query): Builder
    {
        $this->filters()->each->__invoke($this, $query);

        return $query;
    }

    /**
     * Apply the specified ordering to the given query.
     *
     * @template TValue of \Illuminate\Contracts\Database\Eloquent\Builder
     *
     * @param  TValue  $query
     * @param  (callable(TValue): (TValue))|null  $defaultCallback
     * @return TValue
     */
    public function withOrdering(Builder $query, $defaultCallback = null): Builder
    {
        if (! $this->orderBy || ! $this->orderByDirection) {
            with($query, $defaultCallback);

            return $query;
        }

        $model = $this->model();

        $fieldExists = $this->lens()->availableFields($this)
            ->transform(function ($field) use ($model) {
                return $field instanceof RelatableField
                    ? $this->getRelationForeignKeyName($model->{$field->attribute}())
                    : $field->attribute ?? null;
            })->filter()
            ->first(fn ($attribute) => $attribute == $this->orderBy);

        if ($fieldExists) {
            return $query->orderBy(
                ($this->tableOrderPrefix ? table_name($query->getModel()).'.' : '').$this->orderBy,
                $this->orderByDirection === 'asc' ? 'asc' : 'desc'
            );
        }

        return $query;
    }

    /**
     * Disable prepending of the table order.
     *
     * @return $this
     */
    public function withoutTableOrderPrefix()
    {
        $this->tableOrderPrefix = false;

        return $this;
    }

    /**
     * Get all of the possibly available filters for the request.
     */
    protected function availableFilters(): Collection
    {
        return $this->lens()->availableFilters($this);
    }

    /**
     * Map the given models to the appropriate resource for the request.
     */
    public function toResources(Collection $models): Collection
    {
        $resourceClass = $this->resource();

        return $models->map(function ($model) use ($resourceClass) {
            $lensResource = $this->lens()->setResource($model);

            return transform((new $resourceClass($model))->serializeForIndex(
                $this, $lensResource->resolveFields($this)
            ), function ($payload) use ($model, $lensResource) {
                $hasId = ! \is_null($payload['id']->value);

                $payload['actions'] = collect(
                    $hasId === true ? array_values($lensResource->actions($this)) : []
                )->filter(static fn ($action) => $action->shownOnIndex() || $action->shownOnTableRow())
                ->filter->authorizedToSee($this)
                ->filter->authorizedToRun($this, $model)
                ->values();

                return $payload;
            });
        });
    }

    /**
     * Get foreign key name for relation.
     */
    protected function getRelationForeignKeyName(Relation $relation): string
    {
        return method_exists($relation, 'getForeignKeyName')
            ? $relation->getForeignKeyName()
            : $relation->getForeignKey();
    }

    /**
     * Get per page.
     */
    public function perPage(): int
    {
        $resourceClass = $this->resource();

        $perPageOptions = $this->lens()::perPageOptions() ?? $resourceClass::perPageOptions();

        return (int) \in_array($this->perPage, $perPageOptions) ? $this->perPage : $perPageOptions[0];
    }

    /** {@inheritDoc} */
    #[\Override]
    public function isActionRequest(): bool
    {
        return $this->segment(5) == 'actions';
    }
}
