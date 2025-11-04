<?php

namespace Laravel\Nova\Testing\Browser\Components\Modals;

use Laravel\Dusk\Browser;

class DeleteResourceModalComponent extends ModalComponent
{
    /**
     * Modal confirmation button.
     */
    public function confirm(Browser $browser): void
    {
        $browser->click('@confirm-delete-button');
    }

    /**
     * Modal cancelation button.
     */
    public function cancel(Browser $browser): void
    {
        $browser->click('@cancel-delete-button');
    }
}
