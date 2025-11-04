<?php

namespace Laravel\Nova\Fields;

trait SupportsWithTrashedRelatables
{
    /**
     * Indicates whether the field should display the "With Trashed" option.
     */
    public bool $displaysWithTrashed = true;

    /**
     * Determine whether field should display "With Trashed" checkbox.
     *
     * @return $this
     */
    public function displaysWithTrashed(bool $withTrashed = true)
    {
        $this->displaysWithTrashed = $withTrashed;

        return $this;
    }

    /**
     * Hides the "With Trashed" option.
     *
     * @return $this
     */
    public function withoutTrashed()
    {
        $this->displaysWithTrashed(false);

        return $this;
    }
}
