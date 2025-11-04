<?php

namespace Laravel\Nova\Auth\Actions;

use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\JsonResponse;

class FailedPasswordResetLinkRequestResponse extends \Laravel\Fortify\Http\Responses\FailedPasswordResetLinkRequestResponse
{
    /** {@inheritDoc} */
    #[\Override]
    public function toResponse($request)
    {
        if ($this->status === PasswordBroker::INVALID_USER) {
            $message = __('We have emailed your password reset link!');

            return $request->wantsJson()
                ? new JsonResponse(['message' => $message], 200)
                : back()->with('status', $message);
        }

        return parent::toResponse($request);
    }
}
