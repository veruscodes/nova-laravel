<?php

namespace Laravel\Nova\Testing\Browser\Components;

class LensComponent extends IndexComponent
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $resourceName,
        public string $lens
    ) {
        //
    }

    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return '@'.$this->lens.'-lens-component';
    }
}
