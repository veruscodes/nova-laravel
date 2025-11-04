<?php

namespace Laravel\Nova\Jobs\Middleware;

use Closure;
use Laravel\Nova\Http\Requests\NovaRequest;

class EnsureNovaRequestBoundToContainer
{
    /**
     * Process the queued job.
     *
     * @param  \Closure(object): void  $next
     */
    public function handle(object $job, Closure $next): void
    {
        $boundedByMiddleware = false;

        /** @var \Laravel\Nova\Http\Requests\NovaRequest|null $request */
        $request = optional($job)->request ?? null;

        if ($request instanceof NovaRequest) {
            if (! app()->bound(NovaRequest::class)) {
                app()->instance(NovaRequest::class, $request);
                $boundedByMiddleware = true;
            }
        }

        $next($job);

        if ($boundedByMiddleware) {
            app()->forgetInstance(NovaRequest::class);
        }
    }
}
