<?php

namespace Laravel\Nova\Actions\Responses;

use JsonSerializable;
use Stringable;

class Message implements JsonSerializable, Stringable
{
    /**
     * Construct a new response.
     */
    public function __construct(
        public Stringable|string $text
    ) {
        //
    }

    /**
     * Prepare for string serialization.
     */
    public function __toString(): string
    {
        return (string) $this->text;
    }

    /**
     * Prepare for JSON serialization.
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): string
    {
        return (string) $this;
    }
}
