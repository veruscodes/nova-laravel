<?php

namespace Laravel\Nova\Testing\Concerns;

use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests as IlluminateMakesHttpRequests;
use Laravel\Nova\Http\Requests\NovaRequest;

trait MakesHttpRequests
{
    use IlluminateMakesHttpRequests;

    /**
     * Create the request instance used for testing from the given Symfony request.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $symfonyRequest
     * @return \Laravel\Nova\Http\Requests\NovaRequest
     */
    protected function createTestRequest($symfonyRequest)
    {
        return NovaRequest::createFromBase($symfonyRequest);
    }
}
