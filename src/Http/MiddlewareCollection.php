<?php

namespace Laravel\Nova\Http;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * @template TKey of int
 * @template TValue of string|class-string
 *
 * @extends \Illuminate\Support\Collection<TKey, TValue>
 */
class MiddlewareCollection extends Collection
{
    /**
     * Appends authentication middleware after "web" group.
     *
     * @return $this
     */
    public function appendsRedirectIfAuthenticatedMiddleware()
    {
        $found = false;

        return $this->transform(function ($middleware) use (&$found) {
            if ($middleware === 'web') {
                $found = true;

                return ['web', 'nova.guest'];
            }

            return $middleware;
        })->when(
            $found === true,
            fn ($middlewares) => $middlewares->flatten()->values(),
            fn ($middlewares) => $middlewares->push('nova.guest'),
        );
    }

    /**
     * Create middleware group from current collection.
     */
    public function asMiddlewareGroup(string $name): void
    {
        Route::middlewareGroup($name, $this->all());
    }
}
