<?php

namespace Laravel\Nova\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Middleware;
use Inertia\ResponseFactory;
use Laravel\Nova\Http\Resources\UserResource;
use Laravel\Nova\Nova;

class HandleInertiaRequests extends Middleware
{
    /** {@inheritDoc} */
    protected $rootView = 'nova::layout';

    /** {@inheritDoc} */
    #[\Override]
    public function version(Request $request)
    {
        return \sprintf('%s:%s', $this->rootView, parent::version($request));
    }

    /** {@inheritDoc} */
    #[\Override]
    public function share(Request $request)
    {
        return array_merge(parent::share($request), [
            'novaConfig' => static fn () => Nova::jsonVariables($request),
            'currentUser' => static function () use ($request) {
                return with(Nova::user($request), static function ($user) use ($request) {
                    return ! \is_null($user) ? UserResource::make($user)->toArray($request) : null;
                });
            },
            'validLicense' => static function () use ($request) {
                return with(Nova::user($request), static function ($user) {
                    return ! \is_null($user) ? Nova::checkLicenseValidity() : Cache::get('nova_valid_license_key');
                });
            },
        ]);
    }

    /** {@inheritDoc} */
    #[\Override]
    public function handle(Request $request, Closure $next)
    {
        Config::set('inertia.ssr.enabled', false);

        if (method_exists(ResponseFactory::class, 'encryptHistory') && $request->getScheme() === 'https') {
            Inertia::encryptHistory(); // @phpstan-ignore staticMethod.notFound
        }

        return parent::handle($request, $next);
    }
}
