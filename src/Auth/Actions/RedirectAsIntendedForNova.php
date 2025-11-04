<?php

namespace Laravel\Nova\Auth\Actions;

use Laravel\Fortify\Http\Responses\RedirectAsIntended;
use Laravel\Nova\Nova;

class RedirectAsIntendedForNova extends RedirectAsIntended
{
    /** {@inheritDoc} */
    #[\Override]
    public function toResponse($request)
    {
        return redirect()->intended(Nova::initialPathUrl($request));
    }
}
