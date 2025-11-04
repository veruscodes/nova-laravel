<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use LogicException;

class LensActionRequest extends ActionRequest
{
    use InteractsWithLenses;

    /** {@inheritDoc} */
    #[\Override]
    public function toQuery(): Builder
    {
        return tap($this->lens()->query(LensRequest::createFrom($this), $this->newSearchQuery()), static function ($query) {
            if (! $query instanceof Builder) {
                throw new LogicException('Lens must return an Eloquent query instance in order to apply actions.');
            }
        });
    }

    /** {@inheritDoc} */
    #[\Override]
    public function toQueryWithoutScopes(): Builder
    {
        return $this->toQuery();
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function resolveActions(): Collection
    {
        return $this->isPivotAction()
            ? $this->lens()->resolvePivotActions($this)
            : $this->lens()->resolveActions($this);
    }
}
