<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Laravel\Nova\Lenses\Lens;
use Laravel\Nova\Query\Search;

/**
 * @property-read string|null $lens
 */
trait InteractsWithLenses
{
    /**
     * Get the lens instance for the given request.
     */
    public function lens(): Lens
    {
        return $this->availableLenses()->first(function ($lens) {
            return $this->lens === $lens->uriKey();
        }) ?: abort($this->lensExists() ? 403 : 404);
    }

    /**
     * Get all of the possible lenses for the request.
     */
    public function availableLenses(): Collection
    {
        return transform($this->newResource(), function ($resource) {
            abort_unless($resource::authorizedToViewAny($this), 403);

            return $resource->availableLenses($this);
        });
    }

    /**
     * Transform the request into a search query.
     */
    public function newSearchQuery(): Builder
    {
        $lens = $this->lens();

        return $lens::searchable() && ! empty($this->search)
            ? (new Search($this->newQuery(), $this->search))->handle($this->resource(), $lens->searchableColumns())
            : $this->newQuery();
    }

    /**
     * Determine if the specified action exists at all.
     */
    protected function lensExists(): bool
    {
        return $this->newResource()->resolveLenses($this)->contains(function ($lens) {
            return $this->lens === $lens->uriKey();
        });
    }
}
