<?php

namespace Laravel\Nova\Http\Controllers\Fortify;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController as Controller;

class AuthenticatedSessionController extends Controller
{
    /** {@inheritDoc} */
    #[\Override]
    public function destroy(Request $request): LogoutResponse
    {
        $this->guard = app(StatefulGuard::class);

        return parent::destroy($request);
    }
}
