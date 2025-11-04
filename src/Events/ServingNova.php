<?php

namespace Laravel\Nova\Events;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class ServingNova
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Application $app,
        public Request $request
    ) {
        //
    }
}
