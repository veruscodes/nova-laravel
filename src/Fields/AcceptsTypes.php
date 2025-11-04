<?php

namespace Laravel\Nova\Fields;

trait AcceptsTypes
{
    /**
     * The file types accepted by the field.
     *
     * @var string|null
     */
    public $acceptedTypes;

    /**
     * Set the fields accepted file types.
     *
     * @return $this
     */
    public function acceptedTypes(string $acceptedTypes)
    {
        $this->acceptedTypes = $acceptedTypes;

        return $this;
    }
}
