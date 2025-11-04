<?php

namespace Laravel\Nova\Listeners;

use Laravel\Nova\Nova;
use Laravel\Nova\NovaServiceProvider;
use Laravel\Nova\Tools\Dashboard;
use Laravel\Nova\Tools\ResourceManager;

class BootNova
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if (! app()->providerIsLoaded(NovaServiceProvider::class)) {
            app()->register(NovaServiceProvider::class);
        }

        $this->registerTools();
        $this->registerResources();
    }

    /**
     * Boot the standard Nova resources.
     */
    protected function registerResources(): void
    {
        Nova::resources([
            Nova::actionResource(),
        ]);

        Nova::bootResources();
    }

    /**
     * Boot the standard Nova tools.
     */
    protected function registerTools(): void
    {
        Nova::tools([
            new Dashboard,
            new ResourceManager,
        ]);
    }
}
