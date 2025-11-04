<?php

namespace Laravel\Nova\Testing\Browser\Components\Modals;

use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Components\Component;

abstract class ModalComponent extends Component
{
    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return '.modal[data-modal-open=true]';
    }

    /**
     * Modal confirmation button.
     */
    abstract public function confirm(Browser $browser): void;

    /**
     * Modal cancelation button.
     */
    abstract public function cancel(Browser $browser): void;
}
