<?php

namespace Laravel\Nova;

use Closure;
use Illuminate\Http\Request;

trait AuthorizedToSee
{
    /**
     * The callback used to authorize viewing the filter or action.
     *
     * @var (\Closure(\Laravel\Nova\Http\Requests\NovaRequest|\Illuminate\Http\Request):(bool))|null
     */
    public $seeCallback = null;

    /**
     * Determine if the filter or action should be available for the given request.
     *
     * @return bool
     */
    public function authorizedToSee(Request $request)
    {
        return \is_callable($this->seeCallback)
            ? \call_user_func($this->seeCallback, $request) // @phpstan-ignore argument.type
            : true;
    }

    /**
     * Set the callback to be run to authorize viewing the filter or action.
     *
     * @param  \Closure(\Laravel\Nova\Http\Requests\NovaRequest|\Illuminate\Http\Request):bool  $callback
     * @return $this
     */
    public function canSee(Closure $callback)
    {
        $this->seeCallback = $callback;

        return $this;
    }
}
