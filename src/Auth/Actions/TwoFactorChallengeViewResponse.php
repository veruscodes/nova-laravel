<?php

namespace Laravel\Nova\Auth\Actions;

use Inertia\Inertia;
use Laravel\Fortify\Contracts\TwoFactorChallengeViewResponse as Responsable;

class TwoFactorChallengeViewResponse implements Responsable
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        return Inertia::render('Nova.TwoFactorChallenge')->toResponse($request);
    }
}
