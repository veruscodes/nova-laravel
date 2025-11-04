<?php

namespace Laravel\Nova\Auth\Actions;

use Inertia\Inertia;
use Laravel\Fortify\Contracts\ResetPasswordViewResponse as Responsable;
use Laravel\Nova\Nova;

class ResetPasswordViewResponse implements Responsable
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $token = $request->route()->parameter('token');

        return Inertia::render('Nova.ResetPassword', [
            'token' => $token,
            'email' => $request->{Nova::fortify()->email},
        ])->toResponse($request);
    }
}
