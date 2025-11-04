<?php

namespace Laravel\Nova\Http\Controllers\Fortify;

use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Http\Controllers\NewPasswordController as Controller;

class NewPasswordController extends Controller
{
    /** {@inheritDoc} */
    #[\Override]
    public function store(Request $request): Responsable
    {
        $this->guard = app(StatefulGuard::class);

        return parent::store($request);
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function broker(): PasswordBroker
    {
        return Password::broker(config('nova.passwords'));
    }
}
