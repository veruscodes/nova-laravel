<?php

namespace Laravel\Nova\Actions;

use Illuminate\Support\Str;

class ActionMethod
{
    /**
     * Determine the appropriate "handle" method for the given models.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public static function determine(Action $action, $model): string
    {
        if (! \is_null($action->handleCallback)) {
            return 'handleUsingCallback';
        }

        $method = 'handleFor'.Str::plural(class_basename($model));

        if (method_exists($action, $method)) {
            return $method;
        }

        return 'handle';
    }
}
