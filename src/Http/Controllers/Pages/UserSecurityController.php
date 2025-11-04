<?php

namespace Laravel\Nova\Http\Controllers\Pages;

use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;
use Laravel\Nova\Auth\Concerns\ConfirmsTwoFactorAuthentication;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

class UserSecurityController extends Controller
{
    use ConfirmsTwoFactorAuthentication;

    /**
     * Show User Security page.
     */
    public function show(NovaRequest $request): Response
    {
        abort_unless(Features::hasSecurityFeatures(), 404);

        $this->validateTwoFactorAuthenticationState($request);

        return Inertia::render('Nova.UserSecurity', array_filter([
            'options' => config('fortify-options', []),
            'user' => transform(Nova::user($request), static fn ($user) => [
                'two_factor_enabled' => Features::enabled(Features::twoFactorAuthentication())
                    && ! \is_null($user->two_factor_secret), // @phpstan-ignore property.notFound
            ]),
        ]));
    }
}
