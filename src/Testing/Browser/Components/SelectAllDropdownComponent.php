<?php

namespace Laravel\Nova\Testing\Browser\Components;

use Laravel\Dusk\Browser;

class SelectAllDropdownComponent extends Component
{
    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return '@select-all-dropdown';
    }

    /**
     * Assert checkbox status.
     */
    protected function assertCheckboxStatus(Browser $browser, string $status): void
    {
        $browser->assertAttribute('@select-all-indicator', 'data-state', $status);
    }

    /**
     * Assert that the checkbox is checked.
     */
    public function assertCheckboxIsChecked(Browser $browser): void
    {
        $this->assertCheckboxStatus($browser, 'checked');
    }

    /**
     * Assert that the checkbox is not checked.
     */
    public function assertCheckboxIsNotChecked(Browser $browser): void
    {
        $this->assertCheckboxStatus($browser, 'unchecked');
    }

    /**
     * Assert that the checkbox is indeterminate.
     */
    public function assertCheckboxIsIndeterminate(Browser $browser): void
    {
        $this->assertCheckboxStatus($browser, 'indeterminate');
    }

    /**
     * Assert select all the the resources on current page is checked.
     */
    public function assertSelectAllOnCurrentPageChecked(Browser $browser): void
    {
        $this->assertCheckboxIsIndeterminate($browser);
    }

    /**
     * Assert select all the the resources on current page isn't checked.
     */
    public function assertSelectAllOnCurrentPageNotChecked(Browser $browser): void
    {
        $this->assertCheckboxIsNotChecked($browser);
    }

    /**
     * Assert select all the matching resources is checked.
     */
    public function assertSelectAllMatchingChecked(Browser $browser): void
    {
        $browser
            ->waitFor('@select-all-dropdown-trigger')
            ->click('@select-all-dropdown-trigger')
            ->elsewhereWhenAvailable('@dropdown-menu', static function (Browser $browser) {
                $browser->assertAttribute('@select-all-matching-button', 'data-state', 'checked');
            })
            ->closeCurrentDropdown();
    }

    /**
     * Assert select all the matching resources isn't checked.
     */
    public function assertSelectAllMatchingNotChecked(Browser $browser): void
    {
        $browser
            ->waitFor('@select-all-dropdown-trigger')
            ->click('@select-all-dropdown-trigger')
            ->elsewhereWhenAvailable('@dropdown-menu', static function (Browser $browser) {
                $browser->assertAttribute('@select-all-matching-button', 'data-state', 'unchecked');
            })
            ->closeCurrentDropdown();
    }

    /**
     * Assert on the total selected count text.
     */
    public function assertSelectedCount(Browser $browser, int $count): void
    {
        $browser->assertSeeIn('span.font-bold', "{$count} selected");
    }

    /**
     * Assert on the matching total matching count text.
     */
    public function assertSelectAllMatchingCount(Browser $browser, int $count): void
    {
        $browser
            ->waitFor('@select-all-dropdown-trigger')
            ->click('@select-all-dropdown-trigger')
            ->elsewhereWhenAvailable('@dropdown-menu', static function (Browser $browser) use ($count) {
                $browser->assertSeeIn('@select-all-matching-count', $count);
            })
            ->closeCurrentDropdown();
    }

    /**
     * Select all the the resources on current page.
     */
    public function selectAllOnCurrentPage(Browser $browser): void
    {
        $browser
            ->waitFor('@select-all-dropdown-trigger')
            ->click('@select-all-dropdown-trigger')
            ->elsewhereWhenAvailable('@dropdown-menu', static function (Browser $browser) {
                $browser->click('@select-all-button');
            })
            ->closeCurrentDropdown();
    }

    /**
     * Un-select all the the resources on current page.
     */
    public function unselectAllOnCurrentPage(Browser $browser): void
    {
        $browser->waitFor('@deselect-all-button')
            ->click('@deselect-all-button')->pause(250);
    }

    /**
     * Select all the matching resources.
     */
    public function selectAllMatching(Browser $browser): void
    {
        $browser
            ->waitFor('@select-all-dropdown-trigger')
            ->click('@select-all-dropdown-trigger')
            ->elsewhereWhenAvailable('@dropdown-menu', static function (Browser $browser) {
                $browser->click('@select-all-matching-button');
            })
            ->closeCurrentDropdown();
    }

    /**
     * Un-select all the matching resources.
     */
    public function unselectAllMatching(Browser $browser): void
    {
        $browser
            ->waitFor('@select-all-dropdown-trigger', 2)
            ->click('@select-all-dropdown-trigger')
            ->elsewhereWhenAvailable('@dropdown-menu', static function (Browser $browser) {
                $browser->click('@select-all-matching-button')->pause(250);
            }, 2);
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
}
