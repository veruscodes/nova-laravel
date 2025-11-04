<?php

namespace Laravel\Nova;

class Script extends Asset
{
    /**
     * Get the Asset URL.
     */
    public function url(): string
    {
        if (! $this->isRemote()) {
            return "/nova-api/scripts/{$this->name}";
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
            'Content-Type' => 'application/javascript',
        ];
    }
}
