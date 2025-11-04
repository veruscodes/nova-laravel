<?php

namespace Laravel\Nova\Http\Controllers\Fortify;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController as Controller;

class ConfirmablePasswordController extends Controller
{
    /** {@inheritDoc} */
    #[\Override]
    public function store(Request $request)
    {
        $this->guard = app(StatefulGuard::class);

        return parent::store($request);
    }
}
