<?php

namespace Laravel\Nova\Testing\Browser\Pages;

use Illuminate\Database\Eloquent\Model;
use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Components\Modals\CreateRelationModalComponent;

class Update extends Page
{
    use InteractsWithRelations;

    /**
     * The Resource ID.
     *
     * @var \Illuminate\Database\Eloquent\Model|string|int
     */
    public mixed $resourceId;

    /**
     * Create a new page instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string|int  $resourceId
     * @param  array<string, mixed>  $queryParams
     */
    public function __construct(
        public string $resourceName,
        mixed $resourceId,
        array $queryParams = []
    ) {
        $this->resourceId = $resourceId instanceof Model ? $resourceId->getKey() : $resourceId;

        $this->setNovaPage("/resources/{$this->resourceName}/{$this->resourceId}/edit", $queryParams);
    }

    /**
     * Run the inline create relation.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function runInlineCreate(Browser $browser, string $uriKey, callable $fieldCallback): void
    {
        $browser->whenAvailable("@{$uriKey}-inline-create", static function (Browser $browser) use ($fieldCallback) {
            $browser->click('')
                ->elsewhereWhenAvailable(new CreateRelationModalComponent, static function (Browser $browser) use ($fieldCallback) {
                    $fieldCallback($browser);

                    $browser->confirm()->pause(250);
                });
        });
    }

    /**
     * Click the update button.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function update(Browser $browser): void
    {
        $browser->dismissToasted()
            ->waitFor('@update-button')
            ->click('@update-button')
            ->pause(500);
    }

    /**
     * Click the update and continue editing button.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function updateAndContinueEditing(Browser $browser): void
    {
        $browser->dismissToasted()
            ->waitFor('@update-and-continue-editing-button')
            ->click('@update-and-continue-editing-button')
            ->pause(500);
    }

    /**
     * Click the cancel button.
     */
    public function cancel(Browser $browser): void
    {
        $browser->dismissToasted()
            ->click('@cancel-update-button');
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertOk()->waitFor('@nova-form');
    }
}
