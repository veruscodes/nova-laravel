<?php

namespace Laravel\Nova\Filters;

use Stringable;

class FilterEncoder implements Stringable
{
    /**
     * Create a new filter encoder instance.
     */
    public function __construct(public array $filters = [])
    {
        //
    }

    /**
     * Prepare for string serialization.
     */
    public function __toString(): string
    {
        return $this->encode();
    }

    /**
     * Encode the filters into a query string.
     *
     * @return string
     */
    public function encode()
    {
        return base64_encode(json_encode($this->filters));
    }
}
