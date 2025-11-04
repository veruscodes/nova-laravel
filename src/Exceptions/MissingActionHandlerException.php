<?php

namespace Laravel\Nova\Exceptions;

use Exception;

class MissingActionHandlerException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  object  $action
     * @return static
     */
    public static function make($action, string $method)
    {
        return new static('Action handler ['.$action::class.'@'.$method.'] not defined.');
    }
}
