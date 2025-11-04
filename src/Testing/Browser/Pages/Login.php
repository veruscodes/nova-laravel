<?php

namespace Laravel\Nova\Testing\Browser\Pages;

use Laravel\Dusk\Browser;

class Login extends Page
{
    /**
     * Create a new page instance.
     */
    public function __construct()
    {
        parent::__construct('/login');
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertOk();
    }

    /**
     * Assert page not found.
     */
    public function assertOk(Browser $browser): void
    {
        $browser->waitForLocation($this->novaPageUrl)->assertPathIs($this->novaPageUrl);
    }
}
