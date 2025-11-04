<?php

namespace Laravel\Nova;

use Closure;

trait AuthorizesRequests
{
    /**
     * The callback that should be used to authenticate Nova users.
     *
     * @var (\Closure(\Illuminate\Http\Request):(bool))|null
     */
    public static $authUsing;

    /**
     * Register the Nova authentication callback.
     *
     * @param  \Closure(\Illuminate\Http\Request):bool  $callback
     */
    public static function auth(Closure $callback): static
    {
        static::$authUsing = $callback;

        return new static;
    }

    /**
     * Determine if the given request can access the Nova dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public static function check($request): bool
    {
        return (static::$authUsing ?: static function () {
            return app()->environment('local');
        })($request);
    }
}
