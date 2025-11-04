<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Nova\Contracts\ImpersonatesUsers;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Util;

class ImpersonateController extends Controller
{
    /**
     * Start impersonating a user.
     */
    public function startImpersonating(NovaRequest $request, ImpersonatesUsers $impersonator): JsonResponse
    {
        if ($impersonator->impersonating($request)) {
            return $this->stopImpersonating($request, $impersonator);
        }

        /** @var class-string<\Illuminate\Contracts\Auth\Authenticatable&\Illuminate\Database\Eloquent\Model> $userModel */
        $userModel = with(
            Nova::modelInstanceForKey($request->input('resource')),
            static fn ($model) => ! \is_null($model) ? $model::class : Util::userModel()
        );

        $authGuard = Util::sessionAuthGuardForModel($userModel);

        $currentUser = Nova::user($request);

        /** @var \Illuminate\Contracts\Auth\Authenticatable&\Illuminate\Database\Eloquent\Model $user */
        $user = $userModel::findOrFail($request->input('resourceId'));

        // Now that we're guaranteed to be a 'real' user, we'll make sure we're
        // actually trying to impersonate someone besides ourselves, as that
        // would be unnecessary.
        if (! $currentUser->is($user)) {
            abort_unless(optional($currentUser)->canImpersonate() ?? false, 403);
            abort_unless(optional($user)->canBeImpersonated() ?? false, 403);

            $impersonator->impersonate(
                $request,
                Auth::guard($authGuard),
                $user
            );
        }

        return $impersonator->redirectAfterStartingImpersonation($request);
    }

    /**
     * Stop impersonating a user.
     */
    public function stopImpersonating(NovaRequest $request, ImpersonatesUsers $impersonator): JsonResponse
    {
        $redirectTo = $request->session()->has('nova_impersonated_from') ? $request->session()->get('nova_impersonated_from') : null;

        $impersonator->stopImpersonating(
            $request,
            Auth::guard(config('nova.guard', config('auth.defaults.guard'))),
            Util::userModel()
        );

        return $impersonator->redirectAfterStoppingImpersonation($request);
    }
}
