<?php

namespace Laravel\Nova\Auth\Actions;

use Inertia\Inertia;
use Laravel\Fortify\Contracts\ConfirmPasswordViewResponse as Responsable;

class ConfirmPasswordViewResponse implements Responsable
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        return Inertia::render('Nova.ConfirmPassword')->toResponse($request);
    }
}
