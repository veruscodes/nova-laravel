<?php

namespace Laravel\Nova\Auth\Actions;

use Inertia\Inertia;
use Laravel\Fortify\Contracts\RequestPasswordResetLinkViewResponse as Responsable;

class RequestPasswordResetLinkViewResponse implements Responsable
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        return Inertia::render('Nova.ForgotPassword')->toResponse($request);
    }
}
