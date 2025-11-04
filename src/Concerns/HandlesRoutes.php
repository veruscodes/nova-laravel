<?php

namespace Laravel\Nova\Concerns;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Support\Facades\Route;
use Laravel\Nova\PendingRouteRegistration;

trait HandlesRoutes
{
    /**
     * The initial path Nova should route to when visiting the base.
     *
     * @var (\Closure(\Illuminate\Http\Request):(string|null))|string
     */
    public static Closure|string $initialPath = '/dashboards/main';

    /**
     * The routes resolvers.
     */
    public static ?PendingRouteRegistration $routesResolver = null;

    /**
     * Get the URI path prefix utilized by Nova.
     */
    public static function path(): string
    {
        return config('nova.path', '/nova');
    }

    /**
     * Get url for Laravel Nova.
     */
    public static function url(?string $url = null): string
    {
        return rtrim(static::path(), '/').'/'.ltrim((string) $url, '/');
    }

    /**
     * Get Route Registrar for Nova.
     *
     * @param  array<int, class-string|string>|null  $middleware
     */
    public static function router(?array $middleware = null, ?string $prefix = null): RouteRegistrar
    {
        return Route::domain(config('nova.domain', null))
                    ->prefix(static::url($prefix))
                    ->middleware($middleware ?? config('nova.middleware', []));
    }

    /**
     * Register the Nova routes.
     */
    public static function routes(): PendingRouteRegistration
    {
        return static::$routesResolver ??= new PendingRouteRegistration;
    }

    /**
     * Set the initial route path when visiting the base Nova url.
     *
     * @param  (\Closure(\Illuminate\Http\Request):(string|null))|string  $path
     */
    public static function initialPath(Closure|string $path): static
    {
        static::$initialPath = $path;

        return new static;
    }

    /**
     * Resolve the user's initial path.
     */
    public static function resolveInitialPath(Request $request): string
    {
        /** @phpstan-ignore nullCoalesce.expr */
        return once(fn () => value(static::$initialPath, $request) ?? '/dashboards/main');
    }

    /**
     * Get the user's intial path URL.
     */
    public static function initialPathUrl(Request $request): string
    {
        return static::url(static::resolveInitialPath($request));
    }
}
