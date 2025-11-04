<?php

namespace Laravel\Nova\Testing\Browser\Components\Modals;

use Laravel\Dusk\Browser;

class RestoreResourceModalComponent extends ModalComponent
{
    /**
     * Modal confirmation button.
     */
    public function confirm(Browser $browser): void
    {
        $browser->click('@confirm-restore-button');
    }

    /**
     * Modal cancelation button.
     */
    public function cancel(Browser $browser): void
    {
        $browser->click('@cancel-restore-button');
    }
}
