<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Support\Collection;

class CardRequest extends NovaRequest
{
    /**
     * Get all of the possible metrics for the request.
     */
    public function availableCards(): Collection
    {
        $resource = $this->newResource();

        if ($this->resourceId) {
            return $resource->availableCardsForDetail($this);
        }

        return $resource->availableCards($this);
    }
}
