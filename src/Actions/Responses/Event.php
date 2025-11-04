<?php

namespace Laravel\Nova\Actions\Responses;

use JsonSerializable;

class Event implements JsonSerializable
{
    /**a
     * Construct a new response.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $key,
        public array $payload = [],
    ) {
        //
    }

    /**
     * Prepare for JSON serialization.
     *
     * @return array{key: string, payload: array<string, mixed>}
     */
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'payload' => $this->payload,
        ];
    }
}
