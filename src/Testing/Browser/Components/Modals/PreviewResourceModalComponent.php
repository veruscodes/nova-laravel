<?php

namespace Laravel\Nova\Testing\Browser\Components\Modals;

use Laravel\Dusk\Browser;

class PreviewResourceModalComponent extends ModalComponent
{
    /**
     * Modal confirmation button.
     */
    public function confirm(Browser $browser): void
    {
        $browser->click('@confirm-preview-button');
    }

    /**
     * Modal cancelation button.
     */
    public function cancel(Browser $browser): void
    {
        $browser->click('@confirm-preview-button');
    }

    /**
     * Modal view detail button.
     */
    public function view(Browser $browser): void
    {
        $browser->click('@detail-preview-button');
    }

    /**
     * Assert modal view detail button is visible.
     */
    public function assertViewButtonVisible(Browser $browser): void
    {
        $browser->assertVisible('@detail-preview-button');
    }
}
