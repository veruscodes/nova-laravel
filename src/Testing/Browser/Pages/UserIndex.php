<?php

namespace Laravel\Nova\Testing\Browser\Pages;

class UserIndex extends Index
{
    /**
     * Create a new page instance.
     *
     * @param  array<string, mixed>  $queryParams
     */
    public function __construct(array $queryParams = [])
    {
        parent::__construct('users', $queryParams);
    }
}
