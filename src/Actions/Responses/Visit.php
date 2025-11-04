<?php

namespace Laravel\Nova\Actions\Responses;

use JsonSerializable;
use Laravel\Nova\URL;

class Visit implements JsonSerializable
{
    /**
     * Construct a new response.
     */
    public function __construct(
        public URL|string $path,
        public array $options = [],
    ) {
        //
    }

    /**
     * Prepare for JSON serialization.
     *
     * @return array{path: string, options: array<string, mixed>}
     */
    public function jsonSerialize(): array
    {
        return [
            'path' => '/'.ltrim($this->path, '/'),
            'options' => $this->options,
        ];
    }
}
