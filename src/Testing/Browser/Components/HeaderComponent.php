<?php

namespace Laravel\Nova\Testing\Browser\Components;

use Closure;
use Laravel\Dusk\Browser;

class HeaderComponent extends Component
{
    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return 'div#app header';
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
     * Open notification panel.
     */
    public function showNotificationPanel(Browser $browser, ?Closure $notificationCallback = null): void
    {
        $browser
            ->click('@notifications-dropdown')
            ->elsewhereWhenAvailable('@notifications-content', $notificationCallback ?? static function (Browser $browser) {
                //
            });
    }

    /**
     * Get the element shortcuts for the component.
     */
    public function elements(): array
    {
        return [];
    }
}
