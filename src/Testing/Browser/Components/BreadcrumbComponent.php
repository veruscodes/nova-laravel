<?php

namespace Laravel\Nova\Testing\Browser\Components;

use Laravel\Dusk\Browser;

class BreadcrumbComponent extends Component
{
    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return 'nav[aria-label="breadcrumb"]';
    }

    /**
     * Assert current page match the title.
     */
    public function assertCurrentPageTitle(Browser $browser, string $title): void
    {
        $browser->assertSeeIn('@current-page', $title);
    }

    /**
     * Get the element shortcuts for the page.
     */
    public function elements(): array
    {
        return [
            '@current-page' => 'li[aria-current="page"]',
        ];
    }
}
