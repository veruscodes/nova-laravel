<?php

namespace Laravel\Nova\Http\Controllers\Fortify;

use Illuminate\Contracts\Auth\StatefulGuard;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController as Controller;
use Laravel\Fortify\Http\Requests\TwoFactorLoginRequest;

class TwoFactorAuthenticatedSessionController extends Controller
{
    /** {@inheritDoc} */
    #[\Override]
    public function store(TwoFactorLoginRequest $request)
    {
        $this->guard = app(StatefulGuard::class);

        return parent::store($request);
    }
}
