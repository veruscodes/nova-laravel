<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Support\Collection;

class LensCardRequest extends CardRequest
{
    use InteractsWithLenses;

    /** {@inheritDoc} */
    #[\Override]
    public function availableCards(): Collection
    {
        return $this->lens()->availableCards($this);
    }
}
