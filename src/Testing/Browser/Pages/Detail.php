<?php

namespace Laravel\Nova\Testing\Browser\Pages;

use Illuminate\Database\Eloquent\Model;
use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Components\ActionDropdownComponent;
use Laravel\Nova\Testing\Browser\Components\IndexComponent;
use Laravel\Nova\Testing\Browser\Components\Modals\DeleteResourceModalComponent;
use Laravel\Nova\Testing\Browser\Components\Modals\RestoreResourceModalComponent;

class Detail extends Page
{
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
     */
    public function __construct(
        public string $resourceName,
        mixed $resourceId,
        array $queryParams = []
    ) {
        $this->resourceId = $resourceId instanceof Model ? $resourceId->getKey() : $resourceId;

        $this->setNovaPage("/resources/{$this->resourceName}/{$this->resourceId}", $queryParams);
    }

    /**
     * Run the action with the given URI key.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function runAction(Browser $browser, string $uriKey): void
    {
        $browser->openControlSelector()
            ->elsewhereWhenAvailable(new ActionDropdownComponent, static function (Browser $browser) use ($uriKey) {
                $browser->runWithConfirmation($uriKey);
            });
    }

    /**
     * Run the action with the given URI key.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function runInstantAction(Browser $browser, string $uriKey): void
    {
        $browser->openControlSelector()
            ->elsewhereWhenAvailable(new ActionDropdownComponent, static function (Browser $browser) use ($uriKey) {
                $browser->runWithoutConfirmation($uriKey);
            });
    }

    /**
     * Open the action modal but cancel the action.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function cancelAction(Browser $browser, string $uriKey): void
    {
        $browser->openControlSelector()
            ->elsewhereWhenAvailable(new ActionDropdownComponent, static function (Browser $browser) use ($uriKey) {
                $browser->cancel($uriKey);
            });
    }

    /**
     * Edit the resource.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function edit(Browser $browser): void
    {
        $browser->waitFor('@edit-resource-button')
            ->click('@edit-resource-button');
    }

    /**
     * Create the related resource.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function runCreateRelation(Browser $browser, string $relatedResourceName): void
    {
        $browser->within(new IndexComponent($relatedResourceName), static function (Browser $browser) {
            $browser->waitFor('@create-button')->click('@create-button');
        })->on(new Create($relatedResourceName));
    }

    /**
     * Create the related resource.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function runAttachRelation(Browser $browser, string $relatedResourceName, ?string $viaRelationship = null): void
    {
        $browser->within(new IndexComponent($relatedResourceName, $viaRelationship), static function (Browser $browser) {
            $browser->waitFor('@attach-button')->click('@attach-button');
        })->on(new Attach($this->resourceName, $this->resourceId, $relatedResourceName, $viaRelationship));
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
     * Replicate the resource.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function replicate(Browser $browser): void
    {
        $browser->openControlSelector()
            ->whenAvailable("@{$this->resourceId}-replicate-button", static function (Browser $browser) {
                $browser->click('');
            });
    }

    /**
     * Delete the resource.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function delete(Browser $browser): void
    {
        $browser->openControlSelector()
            ->whenAvailable('@open-delete-modal-button', static function (Browser $browser) {
                $browser->click('');
            })
            ->elsewhereWhenAvailable(new DeleteResourceModalComponent, static function (Browser $browser) {
                $browser->confirm();
            })->pause(1000);
    }

    /**
     * Restore the resource.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function restore(Browser $browser): void
    {
        $browser->openControlSelector()
            ->whenAvailable('@open-restore-modal-button', static function (Browser $browser) {
                $browser->click('');
            })
            ->elsewhereWhenAvailable(new RestoreResourceModalComponent, static function (Browser $browser) {
                $browser->confirm();
            })->pause(1000);
    }

    /**
     * Force delete the resource.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function forceDelete(Browser $browser): void
    {
        $browser->openControlSelector()
            ->whenAvailable('@open-force-delete-modal-button', static function (Browser $browser) {
                $browser->click('');
            })
            ->elsewhereWhenAvailable(new DeleteResourceModalComponent, static function (Browser $browser) {
                $browser->confirm();
            })->pause(1000);
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertOk()->waitFor('@nova-resource-detail');
    }

    /**
     * Get the element shortcuts for the page.
     */
    public function elements(): array
    {
        return [
            '@nova-resource-detail' => '[dusk="'.$this->resourceName.'-detail-component"]',
        ];
    }
}
