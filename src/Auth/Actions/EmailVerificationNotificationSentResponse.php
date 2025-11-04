<?php

namespace Laravel\Nova\Auth\Actions;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse as Responsable;
use Laravel\Fortify\Fortify;

class EmailVerificationNotificationSentResponse implements Responsable
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? new JsonResponse([
                'status' => Fortify::VERIFICATION_LINK_SENT,
            ], 200)
            : back()->with('status', Fortify::VERIFICATION_LINK_SENT);
    }
}
