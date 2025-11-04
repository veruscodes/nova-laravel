<?php

namespace Laravel\Nova\Notifications;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Notifications\Notification as LaravelNotification;
use Laravel\Nova\Exceptions\HelperNotSupported;
use Laravel\Nova\Makeable;
use Laravel\Nova\Nova;
use Laravel\Nova\URL;
use Laravel\Nova\WithComponent;
use Stringable;

class NovaNotification extends LaravelNotification implements Arrayable
{
    use Makeable;
    use WithComponent;

    public const SUCCESS_TYPE = 'success';

    public const ERROR_TYPE = 'error';

    public const WARNING_TYPE = 'warning';

    public const INFO_TYPE = 'info';

    /**
     * The notification available types text CSS.
     *
     * @var array
     */
    public static $types = [
        self::SUCCESS_TYPE => 'text-green-500',
        self::ERROR_TYPE => 'text-red-500',
        self::WARNING_TYPE => 'text-yellow-500',
        self::INFO_TYPE => 'text-sky-500',
    ];

    /**
     * The component used for the notification.
     *
     * @var string
     */
    public $component = 'message-notification';

    /**
     * The icons used for the notification.
     *
     * @var string
     */
    public $icon = 'exclamation-circle';

    /**
     * The message used for the notification.
     *
     * @var \Stringable|string|null
     */
    public $message = null;

    /**
     * The text used for the call-to-action button label.
     *
     * @var \Stringable|string
     */
    public $actionText = 'View';

    /**
     * The URL used for the call-to-action button.
     *
     * @var \Laravel\Nova\URL|string|null
     */
    public $actionUrl = null;

    /**
     * Determine if URL should be open in new tab.
     *
     * @var bool
     */
    public $openInNewTab = false;

    /**
     * The notification's visual type.
     *
     * @var string
     */
    public $type = 'success';

    /**
     * Set the icon used for the notification.
     *
     * @return $this
     */
    public function icon(string $icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Set the message used for the notification.
     *
     * @return $this
     */
    public function message(Stringable|string $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Set the URL used for the notification call-to-action button.
     *
     * @return $this
     */
    public function url(URL|string $url)
    {
        $this->actionUrl = $url;

        return $this;
    }

    /**
     * Set the action text and URL used for the notification.
     *
     * @return $this
     */
    public function action(string $text, URL|string $url)
    {
        $this->actionText = $text;
        $this->actionUrl = $url;

        return $this;
    }

    /**
     * Set URL to open in new tab.
     *
     * @return $this
     */
    public function openInNewTab()
    {
        if ($this->actionUrl instanceof URL && $this->actionUrl->remote === true) {
            $this->openInNewTab = true;
        } else {
            throw new HelperNotSupported(\sprintf('The %s helper method is only applicable on remote URL.', __METHOD__));
        }

        return $this;
    }

    /**
     * Set the notification's visual type.
     *
     * @return $this
     */
    public function type(string $type = 'success')
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toNova()
    {
        return $this->toArray();
    }

    /**
     * Get the notification channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return [NovaChannel::class];
    }

    /** {@inheritDoc} */
    public function toArray()
    {
        return [
            'component' => $this->component(),
            'icon' => $this->icon,
            'message' => $this->message,
            'actionText' => Nova::__($this->actionText),
            'actionUrl' => $this->actionUrl,
            'openInNewTab' => $this->openInNewTab,
            'type' => $this->type,
            'iconClass' => static::$types[$this->type],
        ];
    }
}
