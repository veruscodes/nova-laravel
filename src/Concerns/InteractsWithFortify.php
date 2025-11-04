<?php

namespace Laravel\Nova\Concerns;

use Laravel\Nova\PendingFortifyConfiguration;

trait InteractsWithFortify
{
    /**
     * The fortify resolvers.
     */
    public static ?PendingFortifyConfiguration $fortifyResolver = null;

    /**
     * Register the Fortify resolver.
     */
    public static function fortify(): PendingFortifyConfiguration
    {
        return static::$fortifyResolver ??= new PendingFortifyConfiguration;
    }
}
