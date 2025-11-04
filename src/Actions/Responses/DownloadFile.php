<?php

namespace Laravel\Nova\Actions\Responses;

use JsonSerializable;
use Stringable;

class DownloadFile implements JsonSerializable
{
    /**
     * Construct a new response.
     */
    public function __construct(
        public string $url,
        public Stringable|string $name,
    ) {
        //
    }

    /**
     * Prepare for JSON serialization.
     *
     * @return array{url: string, name: \Stringable|string}
     */
    public function jsonSerialize(): array
    {
        return [
            'url' => $this->url,
            'name' => $this->name,
        ];
    }
}
