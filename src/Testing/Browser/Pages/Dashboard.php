<?php

namespace Laravel\Nova\Testing\Browser\Pages;

use Laravel\Dusk\Browser;

class Dashboard extends Page
{
    /**
     * Create a new page instance.
     */
    public function __construct(
        public string $dashboardName = 'main'
    ) {
        $this->setNovaPage("/dashboards/{$this->dashboardName}");
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertOk()->waitFor('@nova-dashboard');
    }

    /**
     * Get the element shortcuts for the page.
     */
    public function elements(): array
    {
        return [
            '@nova-dashboard' => "[dusk='dashboard-{$this->dashboardName}']",
        ];
    }
}
