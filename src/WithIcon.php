<?php

namespace Laravel\Nova;

/**
 * @property string|null $icon
 */
trait WithIcon
{
    /**
     * Set the content to be used for the item's icon.
     *
     * @param  string|null  $icon
     * @return $this
     */
    public function withIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }
}
