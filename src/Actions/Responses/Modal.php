<?php

namespace Laravel\Nova\Actions\Responses;

use JsonSerializable;

class Modal implements JsonSerializable
{
    /**
     * Construct a new response.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $component,
        public array $payload = [],
    ) {
        //
    }

    /**
     * Prepare for JSON serialization.
     *
     * @return array{component: string, payload: array<string, mixed>}
     */
    public function jsonSerialize(): array
    {
        return [
            'component' => $this->component,
            'payload' => $this->payload,
        ];
    }
}
