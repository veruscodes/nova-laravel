<?php

namespace Laravel\Nova\Auth\Actions;

use Illuminate\Http\JsonResponse;

class SuccessfulPasswordResetLinkRequestResponse extends \Laravel\Fortify\Http\Responses\SuccessfulPasswordResetLinkRequestResponse
{
    /** {@inheritDoc} */
    #[\Override]
    public function toResponse($request)
    {
        $message = __('We have emailed your password reset link!');

        return $request->wantsJson()
            ? new JsonResponse(['message' => $message], 200)
            : back()->with('status', $message);
    }
}
