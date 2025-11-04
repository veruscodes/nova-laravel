<?php

namespace Laravel\Nova;

use DateTime;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Str;
use Stringable;

/**
 * @method static static make(self|\Stringable|string $name, string|null $path, bool|null $remote = null)
 */
abstract class Asset implements Responsable
{
    use Makeable;

    /**
     * The Assert name.
     *
     * @var \Stringable|string
     */
    protected $name;

    /**
     * The Asset path.
     *
     * @var string|null
     */
    protected $path = null;

    /**
     * Determine Asset is remote.
     *
     * @var bool
     */
    protected $remote = false;

    /**
     * Construct a new Asset instance.
     */
    public function __construct(self|Stringable|string $name, ?string $path, ?bool $remote = null)
    {
        if ($name instanceof self) {
            $this->name = $name->name();
            $this->path = $name->path();
            $this->remote = $name->isRemote();

            return;
        }

        if (\is_null($remote)) {
            $remote = Str::startsWith($path, ['http://', 'https://', '://']);
        }

        $this->name = $name;
        $this->path = $path;
        $this->remote = $remote;
    }

    /**
     * Make a remote URL.
     */
    public static function remote(string $path): static
    {
        return new static(md5($path), $path, true);
    }

    /**
     * Get asset name.
     */
    public function name(): Stringable|string
    {
        return $this->name;
    }

    /**
     * Get asset path.
     */
    public function path(): ?string
    {
        return $this->path;
    }

    /**
     * Determine if URL is remote.
     */
    public function isRemote(): bool
    {
        return $this->remote;
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        abort_if($this->isRemote() || \is_null($this->path), 404);

        return response(
            file_get_contents($this->path), 200, $this->toResponseHeaders(),
        )->setLastModified(DateTime::createFromFormat('U', (string) filemtime($this->path)));
    }

    /**
     * Get the Asset URL.
     */
    abstract public function url(): string;

    /**
     * Get response headers.
     *
     * @return array<string, string>
     */
    abstract public function toResponseHeaders(): array;
}
