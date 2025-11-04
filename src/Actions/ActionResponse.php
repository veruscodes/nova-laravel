<?php

namespace Laravel\Nova\Actions;

use ArrayAccess;
use JsonSerializable;
use Laravel\Nova\Exceptions\HelperNotSupported;
use Laravel\Nova\Makeable;
use Laravel\Nova\URL;
use Stringable;

class ActionResponse implements ArrayAccess, JsonSerializable
{
    use Makeable;

    /**
     * The `message` response object.
     */
    private ?Responses\Message $message = null;

    /**
     * The `danger` response object.
     */
    private ?Responses\Message $danger = null;

    /**
     * The `download` response object.
     */
    private ?Responses\DownloadFile $download = null;

    /**
     * The `event` response object.
     */
    private ?Responses\Event $event = null;

    /**
     * The `redirect` response object.
     */
    private ?Responses\Redirect $redirect = null;

    /**
     * The `visit` response object.
     */
    private ?Responses\Visit $visit = null;

    /**
     * The `modal` response object.
     */
    private ?Responses\Modal $modal = null;

    /**
     * Indicate response are for deleted action.
     */
    private ?bool $deleted = null;

    /**
     * Create a new response using `message`.
     *
     * @return static
     */
    public static function message(Stringable|string $message)
    {
        return tap(new static, static function ($response) use ($message) {
            $response->withMessage($message);
        });
    }

    /**
     * Includes `message` to the current response.
     *
     * @return $this
     */
    public function withMessage(Stringable|string $message)
    {
        $this->message = new Responses\Message($message);

        return $this;
    }

    /**
     * Create a new response using `danger`.
     *
     * @return static
     */
    public static function danger(Stringable|string $message)
    {
        return tap(new static, static function ($response) use ($message) {
            $response->withDangerMessage($message);
        });
    }

    /**
     * Includes `danger` to the current response.
     *
     * @return $this
     */
    public function withDangerMessage(Stringable|string $message)
    {
        $this->danger = new Responses\Message($message);

        return $this;
    }

    /**
     * Create a new response using `deleted`.
     *
     * @return static
     */
    public static function deleted()
    {
        return tap(new static, static function ($response) {
            $response->withDeleted();
        });
    }

    /**
     * Includes `deleted` to the current response.
     *
     * @return $this
     */
    public function withDeleted()
    {
        $this->deleted = true;

        return $this;
    }

    /**
     * Create a new response using `emit`.
     *
     * @param  array<string, mixed>  $data
     * @return static
     */
    public static function emit(string $event, array $data = [])
    {
        return tap(new static, static function ($response) use ($event, $data) {
            $response->withEvent($event, $data);
        });
    }

    /**
     * Includes `event` to the current response.
     *
     * @param  array<string, mixed>  $data
     * @return $this
     */
    public function withEvent(string $event, array $data = [])
    {
        $this->event = new Responses\Event(key: $event, payload: $data);

        return $this;
    }

    /**
     * Create a new response using `redirect`.
     *
     * @return static
     */
    public static function redirect(string $url, bool $openInNewTab = false)
    {
        return tap(new static, static function ($response) use ($url, $openInNewTab) {
            $response->withRedirect($url, $openInNewTab);
        });
    }

    /**
     * Create a new response using `openInNewTab`.
     *
     * @return static
     */
    public static function openInNewTab(string $url)
    {
        return static::redirect($url, openInNewTab: true);
    }

    /**
     * Includes `redirect` to the current response.
     *
     * @return $this
     */
    public function withRedirect(string $url, bool $openInNewTab = false)
    {
        $this->redirect = new Responses\Redirect($url, $openInNewTab);

        return $this;
    }

    /**
     * Indicate redirect to be loaded using a new tab.
     *
     * @return $this
     *
     * @throws \Laravel\Nova\Exceptions\HelperNotSupported
     */
    public function usingNewTab()
    {
        if (! \is_null($this->redirect)) {
            $this->redirect->usingNewTab();
        } else {
            return throw new HelperNotSupported(
                \sprintf('The %s helper method is only available in %s class for redirection response', __METHOD__, __CLASS__)
            );
        }

        return $this;
    }

    /**
     * Create a new response using `visit`.
     *
     * @param  array<string, mixed>  $options
     * @return static
     */
    public static function visit(URL|string $path, array $options = [])
    {
        return tap(new static, static function ($response) use ($path, $options) {
            $response->withVisitOptions($path, $options);
        });
    }

    /**
     * Includes `visit` to the current response.
     *
     * @param  array<string, mixed>  $options
     * @return $this
     */
    public function withVisitOptions(URL|string $path, array $options = [])
    {
        $this->visit = new Responses\Visit($path, $options);

        return $this;
    }

    /**
     * Create a new response using `download`.
     *
     * @return static
     */
    public static function download(Stringable|string $name, string $url)
    {
        return tap(new static, static function ($response) use ($name, $url) {
            $response->withDownload($name, $url);
        });
    }

    /**
     * Includes `download` to the current response.
     *
     * @return $this
     */
    public function withDownload(Stringable|string $name, string $url)
    {
        $this->download = new Responses\DownloadFile($url, $name);

        return $this;
    }

    /**
     * Create a new response using `modal`.
     *
     * @return static
     */
    public static function modal(string $modal, array $data)
    {
        return tap(new static, static function ($response) use ($data, $modal) {
            $response->withModal($modal, $data);
        });
    }

    /**
     * Includes `modal` to the current response.
     *
     * @return $this
     */
    public function withModal(string $modal, array $data = [])
    {
        $this->modal = new Responses\Modal(component: $modal, payload: $data);

        return $this;
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->{$offset});
    }

    /**
     * Get the value for a given offset.
     *
     * @param  string  $offset
     * @return mixed|null
     */
    public function offsetGet($offset): mixed
    {
        return $this->{$offset};
    }

    /**
     * Set the value at the given offset.
     *
     * @param  string  $offset
     */
    public function offsetSet($offset, $value): void
    {
        if (property_exists($this, $offset)) {
            $this->{$offset} = $value;
        }
    }

    /**
     * Unset the value at the given offset.
     *
     * @param  string  $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->{$offset});
    }

    /**
     * Prepare the element for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'danger' => $this->danger,
            'deleted' => $this->deleted,
            'download' => $this->download,
            'event' => $this->event,
            'modal' => $this->modal,
            'message' => $this->message,
            'redirect' => $this->redirect,
            'visit' => $this->visit,
        ]);
    }
}
