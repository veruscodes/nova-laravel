<?php

namespace Laravel\Nova\Http\Middleware;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Session\Middleware\AuthenticateSession as Middleware;

class AuthenticateSession extends Middleware
{
    /** {@inheritDoc} */
    #[\Override]
    protected function guard()
    {
        return app(StatefulGuard::class);
    }
}
