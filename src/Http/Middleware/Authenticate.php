<?php

namespace Laravel\Nova\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Laravel\Nova\Exceptions\AuthenticationException as NovaAuthenticationException;

class Authenticate extends Middleware
{
    /** {@inheritDoc} */
    #[\Override]
    public function handle($request, Closure $next, ...$guards)
    {
        try {
            $guard = config('nova.guard');

            if (! empty($guard)) {
                $guards[] = $guard;
            }

            return parent::handle($request, $next, ...$guards);
        } catch (AuthenticationException $e) {
            throw new NovaAuthenticationException('Unauthenticated.', $e->guards());
        }
    }
}
