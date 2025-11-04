<?php

namespace Laravel\Nova\Http\Controllers\Fortify;

use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Http\Controllers\PasswordController as Controller;

class PasswordController extends Controller
{
    /** {@inheritDoc} */
    #[\Override]
    protected function broker(): PasswordBroker
    {
        return Password::broker(config('nova.passwords'));
    }
}
