<?php

namespace Laravel\Nova\Actions\Responses;

use JsonSerializable;

class Redirect implements JsonSerializable
{
    /**
     * Construct a new response.
     */
    public function __construct(
        public string $url,
        public bool $openInNewTab = false
    ) {
        //
    }

    /**
     * Redirect using new tab.
     *
     * @return $this
     */
    public function usingNewTab()
    {
        $this->openInNewTab = true;

        return $this;
    }

    /**
     * Prepare for JSON serialization.
     *
     * @return array{url: string, openInNewTab: bool}
     */
    public function jsonSerialize(): array
    {
        return [
            'url' => $this->url,
            'openInNewTab' => $this->openInNewTab,
        ];
    }
}
