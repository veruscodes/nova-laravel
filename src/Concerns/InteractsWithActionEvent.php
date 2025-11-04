<?php

namespace Laravel\Nova\Concerns;

use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Actions\ActionEvent;
use Laravel\Nova\Actions\ActionResource;

trait InteractsWithActionEvent
{
    /**
     * Get the configured ActionResource class.
     *
     * @return class-string<\Laravel\Nova\Actions\ActionResource>
     */
    public static function actionResource(): string
    {
        return config('nova.actions.resource') ?? ActionResource::class;
    }

    /**
     * Get a new instance of the configured ActionEvent.
     */
    public static function actionEvent(): Model|ActionEvent
    {
        return static::actionResource()::newModel();
    }

    /**
     * Invoke the callback with an instance of the configured ActionEvent if it is available.
     *
     * @param  callable(\Laravel\Nova\Actions\ActionEvent):mixed  $callback
     */
    public static function usingActionEvent(callable $callback): mixed
    {
        return ! \is_null(config('nova.actions.resource'))
            ? \call_user_func($callback, static::actionEvent()) // @phpstan-ignore argument.type
            : null;
    }

    /**
     * Disable action log entries.
     */
    public static function withoutActionEvents(): static
    {
        config(['nova.actions.resource' => null]);

        return new static;
    }
}
