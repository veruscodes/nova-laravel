<?php

namespace Laravel\Nova\Exceptions;

use Illuminate\Auth\AuthenticationException as BaseAuthenticationException;
use Inertia\Inertia;
use Laravel\Nova\Nova;
use Laravel\Nova\URL;

class AuthenticationException extends BaseAuthenticationException
{
    /**
     * Render the exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'redirect' => $this->location(),
            ], 401);
        } elseif ($request->is('nova-api/*') || $request->is('nova-vendor/*')) {
            return response(null, 401);
        }

        if ($request->inertia() || Nova::routes()->loginPath !== false) {
            return $this->redirectForInertia($request);
        }

        return redirect()->guest($this->location());
    }

    /**
     * Determine the location the user should be redirected to.
     */
    protected function location(): URL|string
    {
        $loginPath = Nova::routes()->loginPath;

        return $loginPath !== false ? $loginPath : Nova::url('login');
    }

    /**
     * Redirect request for Inertia.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function redirectForInertia($request)
    {
        tap(redirect(), static function ($redirect) use ($request) {
            $url = $redirect->getUrlGenerator();

            $intended = $request->method() === 'GET' && $request->route() && ! $request->expectsJson()
                    ? $url->full()
                    : $url->previous();

            if ($intended) {
                $redirect->setIntendedUrl($intended);
            }
        });

        return Inertia::location($this->location());
    }
}
