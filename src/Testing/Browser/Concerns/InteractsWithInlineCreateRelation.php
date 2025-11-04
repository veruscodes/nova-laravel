<?php

namespace Laravel\Nova\Testing\Browser\Concerns;

use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Components\Modals\CreateRelationModalComponent;

trait InteractsWithInlineCreateRelation
{
    /**
     * Run the inline relation.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function showInlineCreate(Browser $browser, string $uriKey, callable $fieldCallback): void
    {
        $browser->whenAvailable("@{$uriKey}-inline-create", static function (Browser $browser) use ($fieldCallback) {
            $browser->click('')
                ->elsewhereWhenAvailable(new CreateRelationModalComponent, static function (Browser $browser) use ($fieldCallback) {
                    $fieldCallback($browser);
                });
        });
    }

    /**
     * Run the inline create relation.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function runInlineCreate(Browser $browser, string $uriKey, callable $fieldCallback): void
    {
        $this->showInlineCreate($browser, $uriKey, static function (Browser $browser) use ($fieldCallback) {
            $fieldCallback($browser);

            $browser->click('@create-button')->pause(250);
        });
    }
}
