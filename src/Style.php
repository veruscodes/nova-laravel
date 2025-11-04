<?php

namespace Laravel\Nova;

class Style extends Asset
{
    /**
     * Get the Asset URL.
     */
    public function url(): string
    {
        if (! $this->isRemote()) {
            return "/nova-api/styles/{$this->name}";
        }

        return $this->path;
    }

    /**
     * Get the response headers for the asset.
     *
     * @return array<string, string>
     */
    public function toResponseHeaders(): array
    {
        return [
            'Content-Type' => 'text/css',
        ];
    }
}
