<?php

namespace Laravel\Nova\Testing\Browser\Pages;

use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Components\FormComponent;
use Laravel\Nova\Testing\Browser\Components\IndexComponent;

class Index extends Page
{
    /**
     * Create a new page instance.
     *
     * @param  array<string, mixed>  $queryParams
     */
    public function __construct(
        public string $resourceName,
        array $queryParams = []
    ) {
        $this->setNovaPage("/resources/{$this->resourceName}", $queryParams);
    }

    /**
     * Create the related resource.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function runCreate(Browser $browser, ?callable $fieldCallback = null): void
    {
        $browser->within(new IndexComponent($this->resourceName), static function (Browser $browser) {
            $browser->waitFor('@create-button')->click('@create-button');
        })->on(new Create($this->resourceName));

        if (! \is_null($fieldCallback)) {
            $browser->within(new FormComponent, static function (Browser $browser) use ($fieldCallback) {
                \call_user_func($fieldCallback, $browser);
            });
        }
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertOk()->waitFor('@nova-resource-index');
    }

    /**
     * Get the element shortcuts for the page.
     */
    public function elements(): array
    {
        return [
            '@nova-resource-index' => '[dusk="'.$this->resourceName.'-index-component"]',
        ];
    }
}
