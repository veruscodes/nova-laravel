<?php

namespace Laravel\Nova\Testing\Browser\Components\Modals;

use Laravel\Dusk\Browser;

class CreateRelationModalComponent extends ModalComponent
{
    /**
     * Modal confirmation button.
     */
    public function confirm(Browser $browser): void
    {
        $browser->click('@create-button');
    }

    /**
     * Modal cancelation button.
     */
    public function cancel(Browser $browser): void
    {
        $browser->click('@cancel-create-button');
    }
}
