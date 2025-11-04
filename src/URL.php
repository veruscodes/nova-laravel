<?php

namespace Laravel\Nova;

use JsonSerializable;
use Stringable;

/**
 * @method static static make(self|string|null $url, bool $remote = false)
 */
class URL implements JsonSerializable, Stringable
{
    use Makeable;

    /**
     * The URL.
     *
     * @var string|null
     */
    public $url;

    /**
     * Determine URL is remote.
     *
     * @var bool
     */
    public $remote;

    /**
     * Construct a new URL instance.
     */
    public function __construct(self|string|null $url, bool $remote = false)
    {
        if ($url instanceof self) {
            $this->url = $url->url;
            $this->remote = $url->remote;

            return;
        }

        $this->url = $url;
        $this->remote = $remote;
    }

    /**
     * Make a remote URL.
     */
    public static function remote(string $url): static
    {
        return new static($url, true);
    }

    /**
     * Get the URL.
     */
    public function get(): ?string
    {
        return $this->remote === true ? $this->url : Nova::url($this->url);
    }

    /**
     * Determine if currently an active URL.
     */
    public function active(): bool
    {
        return with(
            ltrim($this->get(), '/'),
            static fn ($url) => request()->is($url, rtrim($url, '/').'/*')
        );
    }

    /**
     * Convert the URL instance to a string.
     */
    public function __toString(): string
    {
        return $this->get();
    }

    /**
     * Prepare the URL for JSON serialization.
     *
     * @return array{url: string, remote: bool}
     */
    public function jsonSerialize(): array
    {
        return [
            'url' => $this->get(),
            'remote' => $this->remote,
        ];
    }
}
