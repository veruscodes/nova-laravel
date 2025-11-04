<?php

namespace Laravel\Nova\Testing\Browser\Components\Modals;

use Laravel\Dusk\Browser;

class ConfirmActionModalComponent extends ModalComponent
{
    /**
     * Modal confirmation button.
     */
    public function confirm(Browser $browser): void
    {
        $browser->click('@confirm-action-button');
    }

    /**
     * Modal cancelation button.
     */
    public function cancel(Browser $browser): void
    {
        $browser->click('@cancel-action-button');
    }
}
