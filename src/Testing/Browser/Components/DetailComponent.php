<?php

namespace Laravel\Nova\Testing\Browser\Components;

use Illuminate\Database\Eloquent\Model;
use Laravel\Dusk\Browser;

class DetailComponent extends Component
{
    /**
     * The Resource ID.
     *
     * @var \Illuminate\Database\Eloquent\Model|string|int
     */
    public mixed $resourceId;

    /**
     * Create a new component instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string|int  $resourceId
     */
    public function __construct(
        public string $resourceName,
        mixed $resourceId
    ) {
        $this->resourceId = $resourceId instanceof Model ? $resourceId->getKey() : $resourceId;
    }

    /**
     * Open the delete selector.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function openControlSelector(Browser $browser): void
    {
        $browser->whenAvailable("@{$this->resourceId}-control-selector", static function (Browser $browser) {
            $browser->click('');
        })->pause(100);
    }

    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return '@'.$this->resourceName.'-detail-component';
    }

    /**
     * Assert that the browser page contains the component.
     */
    public function assert(Browser $browser): void
    {
        tap($this->selector(), static function (string $selector) use ($browser) {
            $browser->pause(100)
                ->waitFor($selector)
                ->assertVisible($selector);
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
