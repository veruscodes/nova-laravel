<?php

namespace Laravel\Nova;

use Closure;

use function Orchestra\Sidekick\is_safe_callable;

trait WithBadge
{
    /**
     * The badge content for the menu item.
     *
     * @var (\Closure():(\Laravel\Nova\Badge|string|false))|(callable():(\Laravel\Nova\Badge|string|false))|\Laravel\Nova\Badge|string|false|null
     */
    public $badgeCallback;

    /**
     * The type of badge that should represent the item.
     *
     * @var string
     */
    public $badgeType = 'info';

    /**
     * Set the content to be used for the item's badge.
     *
     * @param  \Laravel\Nova\Badge|(callable():(\Laravel\Nova\Badge|string|false))|string  $badgeCallback
     * @return $this
     */
    public function withBadge(Badge|callable|string $badgeCallback, string $type = 'info')
    {
        $this->badgeType = $type;

        if (is_safe_callable($badgeCallback) || $badgeCallback instanceof Badge) {
            $this->badgeCallback = $badgeCallback;
        }

        if (\is_string($badgeCallback)) {
            $this->badgeCallback = static fn () => Badge::make($badgeCallback, $type);
        }

        return $this;
    }

    /**
     * Set the content to be used for the item's badge if the condition matches.
     *
     * @param  \Laravel\Nova\Badge|(callable():(\Laravel\Nova\Badge|string|false))|string  $badgeCallback
     * @param  (\Closure():(bool))|bool  $condition
     * @return $this
     */
    public function withBadgeIf(Badge|callable|string $badgeCallback, string $type, Closure|bool $condition)
    {
        $this->withBadge(function () use ($badgeCallback, $condition) {
            if (value($condition) === true) {
                return \is_callable($badgeCallback) ? \call_user_func($badgeCallback) : $badgeCallback;
            } else {
                return false;
            }
        }, $type);

        return $this;
    }

    /**
     * Resolve the badge for the item.
     *
     * @throws \Exception
     */
    public function resolveBadge(): ?Badge
    {
        if (! \is_callable($this->badgeCallback)) {
            return $this->badgeCallback;
        }

        /** @var \Laravel\Nova\Badge|string|false|null $result */
        $result = \call_user_func($this->badgeCallback);

        if (\is_null($result)) {
            throw new \Exception('A menu item badge must always have a value.');
        }

        return match (true) {
            $result === false => null,
            ! $result instanceof Badge => Badge::make($result, $this->badgeType),
            default => $result,
        };
    }
}
