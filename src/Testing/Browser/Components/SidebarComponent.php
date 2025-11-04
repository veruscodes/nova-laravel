<?php

namespace Laravel\Nova\Testing\Browser\Components;

use Laravel\Dusk\Browser;

class SidebarComponent extends Component
{
    /**
     * The screen size 'desktop', 'responsive'.
     */
    public string $screen = 'desktop';

    /**
     * Create a new component instance.
     */
    public function __construct(?string $screen = null)
    {
        $this->screen = $screen ?? $this->screen;
    }

    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return 'div[dusk="sidebar-menu"][role="navigation"][data-screen="'.$this->screen.'"]';
    }

    /**
     * Assert that the browser page contains the component.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function assert(Browser $browser): void
    {
        tap($this->selector(), static function (string $selector) use ($browser) {
            $browser->scrollIntoView($selector);
        });
    }

    /**
     * Get the element shortcuts for the component.
     */
    public function elements(): array
    {
        return [
            '@current-active-link' => 'a[data-active-link=true]',
        ];
    }
}
