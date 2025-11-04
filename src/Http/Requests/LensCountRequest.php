<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Exceptions\LensCountException;

class LensCountRequest extends NovaRequest
{
    use CountsResources;
    use InteractsWithLenses;

    /**
     * Get the count of the lens resources.
     */
    public function toCount(): int
    {
        return rescue(function () {
            return $this->toQuery()->toBase()->getCountForPagination();
        }, 0);
    }

    /**
     * Transform the request into a query.
     */
    public function toQuery(): Builder
    {
        return tap($this->lens()->query(LensRequest::createFrom($this), $this->newSearchQuery()), static function ($query) {
            if (! $query instanceof Builder) {
                throw new LensCountException('Lens must return an Eloquent query instance in order to count lens resources.');
            }
        });
    }
}
