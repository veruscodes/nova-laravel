<?php

namespace Laravel\Nova\Testing\Browser\Components;

use Laravel\Dusk\Browser;

class ComboBoxInputComponent extends SearchInputComponent
{
    /** {@inheritDoc} */
    #[\Override]
    public function searchInput(Browser $browser, string $search, int $pause = 500): void
    {
        $this->showSearchDropdown($browser);

        $browser->type('input[type="search"]', $search);
    }
}
