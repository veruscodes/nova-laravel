<?php

namespace Laravel\Nova\Support;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use JsonSerializable;
use Stringable;

class PendingTranslation implements JsonSerializable, Stringable
{
    use ForwardsCalls;

    /**
     * The translation transformation callback.
     *
     * @var (callable(\Illuminate\Support\Stringable):(\Stringable|string))|null
     */
    public $transformCallback;

    /**
     * Create a new pending translation.
     *
     * @param  array<string, string>  $replace
     */
    public function __construct(
        public ?string $key = null,
        public array $replace = [],
        public ?string $locale = null
    ) {
        //
    }

    /**
     * Transform the translation.
     *
     * @param  (callable(\Illuminate\Support\Stringable):(\Stringable|string))  $transformCallback
     * @return $this
     */
    public function transform(callable $transformCallback)
    {
        $this->transformCallback = $transformCallback;

        return $this;
    }

    /**
     * Get the resolved value.
     */
    public function value(?string $locale = null): string
    {
        $locale ??= $this->locale;

        return (string) with(Str::of(
            transform(__($this->key, $this->replace, $locale), function ($translation) {
                return \is_string($translation) ? $translation : $this->key;
            }) ?? ''
        ), $this->transformCallback);
    }

    /**
     * Dynamically proxy method calls to Stringable.
     *
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->forwardCallTo(Str::of($this->value()), $method, $parameters);
    }

    /**
     * Get the translation as string.
     */
    public function __toString(): string
    {
        return $this->value();
    }

    /**
     * Get the translation as json.
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): string
    {
        return $this->value();
    }
}
