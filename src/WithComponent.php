<?php

namespace Laravel\Nova;

/**
 * @property string $component
 */
trait WithComponent
{
    /**
     * Get the component name for the element.
     *
     * @return string
     */
    public function component()
    {
        return $this->component;
    }

    /**
     * Set the Vue component key for the panel.
     *
     * @return $this
     */
    public function withComponent(string $component)
    {
        $this->component = $component;

        return $this;
    }
}
