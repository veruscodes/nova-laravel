<?php

namespace Laravel\Nova\Auth\Concerns;

use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

trait ConfirmsTwoFactorAuthentication
{
    /**
     * Validate the two factor authentication state for the request.
     */
    protected function validateTwoFactorAuthenticationState(NovaRequest $request): void
    {
        $user = Nova::user($request);

        if (! Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm') || \is_null($user)) {
            return;
        }

        $currentTime = now()->unix();

        // Notate totally disabled state in session...
        if ($this->twoFactorAuthenticationDisabled($request, $user)) {
            $request->session()->put('two_factor_empty_at', $currentTime);
        }

        // If was previously totally disabled this session but is now confirming, notate time...
        if ($this->hasJustBegunConfirmingTwoFactorAuthentication($request, $user)) {
            $request->session()->put('two_factor_confirming_at', $currentTime);
        }

        // If the profile is reloaded and is not confirmed but was previously in confirming state, disable...
        if ($this->neverFinishedConfirmingTwoFactorAuthentication($request, $user, $currentTime)) {
            app(DisableTwoFactorAuthentication::class)($user);

            $request->session()->put('two_factor_empty_at', $currentTime);
            $request->session()->remove('two_factor_confirming_at');
        }
    }

    /**
     * Determine if two factor authenticatoin is totally disabled.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    protected function twoFactorAuthenticationDisabled(NovaRequest $request, $user): bool
    {
        return \is_null($user->two_factor_secret) &&
            \is_null($user->two_factor_confirmed_at);
    }

    /**
     * Determine if two factor authentication is just now being confirmed within the last request cycle.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    protected function hasJustBegunConfirmingTwoFactorAuthentication(NovaRequest $request, $user): bool
    {
        return ! \is_null($user->two_factor_secret) &&
            \is_null($user->two_factor_confirmed_at) &&
            $request->session()->has('two_factor_empty_at') &&
            \is_null($request->session()->get('two_factor_confirming_at'));
    }

    /**
     * Determine if two factor authentication was never totally confirmed once confirmation started.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    protected function neverFinishedConfirmingTwoFactorAuthentication(NovaRequest $request, $user, int $currentTime): bool
    {
        return ! \array_key_exists('code', $request->session()->getOldInput()) &&
            \is_null($user->two_factor_confirmed_at) &&
            $request->session()->get('two_factor_confirming_at', 0) != $currentTime;
    }
}
