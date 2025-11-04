<?php

namespace Laravel\Nova\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;

interface ImpersonatesUsers
{
    /**
     * Start impersonating a user.
     *
     * @return bool
     */
    public function impersonate(Request $request, StatefulGuard $guard, Authenticatable $user);

    /**
     * Stop impersonating the currently impersonated user and revert to the original session.
     *
     * @return bool
     */
    public function stopImpersonating(Request $request, StatefulGuard $guard, string $userModel);

    /**
     * Determine if a user is currently being impersonated.
     *
     * @return bool
     */
    public function impersonating(Request $request);

    /**
     * Remove any impersonation data from the session.
     *
     * @return void
     */
    public function flushImpersonationData(Request $request);

    /**
     * Redirect an admin after starting impersonation.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function redirectAfterStartingImpersonation(Request $request);

    /**
     * Redirect an admin after finishing impersonation.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function redirectAfterStoppingImpersonation(Request $request);
}
