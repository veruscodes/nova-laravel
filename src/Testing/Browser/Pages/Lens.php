<?php

namespace Laravel\Nova\Testing\Browser\Pages;

use Laravel\Dusk\Browser;

class Lens extends Index
{
    /**
     * Create a new page instance.
     *
     * @param  array<string, mixed>  $queryParams
     */
    public function __construct(
        public string $resourceName,
        public string $lens,
        array $queryParams = []
    ) {
        $this->setNovaPage("/resources/{$this->resourceName}/lens/{$this->lens}", $queryParams);
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertOk()->waitFor('@nova-resource-lens');
    }

    /**
     * Get the element shortcuts for the page.
     */
    public function elements(): array
    {
        return [
            '@nova-resource-lens' => '[dusk="'.$this->lens.'-lens-component"]',
        ];
    }
}
