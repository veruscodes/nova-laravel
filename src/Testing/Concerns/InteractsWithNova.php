<?php

namespace Laravel\Nova\Testing\Concerns;

use Illuminate\Support\Facades\Http;
use Laravel\Nova\Events\NovaServiceProviderRegistered;

trait InteractsWithNova
{
    /**
     * Setup interacts with Nova.
     */
    protected function setUpInteractsWithNova(): void
    {
        Http::fake([
            'nova.laravel.com/*' => Http::response([], 200),
        ]);

        NovaServiceProviderRegistered::dispatch();
    }
}
