<?php

namespace Laravel\Nova\Testing\Browser\Pages;

use Laravel\Dusk\Browser;

class Create extends Page
{
    use InteractsWithRelations;

    /**
     * Create a new page instance.
     *
     * @param  array<string, mixed>  $queryParams
     */
    public function __construct(
        public string $resourceName,
        array $queryParams = []
    ) {
        $this->setNovaPage("/resources/{$this->resourceName}/new", $queryParams);
    }

    /**
     * Click the create button.
     */
    public function create(Browser $browser): void
    {
        $browser->dismissToasted()
            ->click('@create-button')
            ->pause(1000);
    }

    /**
     * Click the create and add another button.
     */
    public function createAndAddAnother(Browser $browser): void
    {
        $browser->dismissToasted()
            ->click('@create-and-add-another-button')
            ->pause(500);
    }

    /**
     * Click the cancel button.
     */
    public function cancel(Browser $browser): void
    {
        $browser->dismissToasted()
            ->click('@cancel-create-button');
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertOk()->waitFor('@nova-form');
    }

    /**
     * Assert that there are no search results.
     */
    public function assertNoRelationSearchResults(Browser $browser, string $resourceName): void
    {
        $browser->assertMissing("@{$resourceName}-search-input-result-0");
    }
}
