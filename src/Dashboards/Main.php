<?php

namespace Laravel\Nova\Dashboards;

use Illuminate\Support\Str;
use Laravel\Nova\Cards\Help;
use Laravel\Nova\Dashboard;

class Main extends Dashboard
{
    /** {@inheritDoc} */
    #[\Override]
    public function name()
    {
        return class_basename($this);
    }

    /** {@inheritDoc} */
    #[\Override]
    public function uriKey()
    {
        return Str::snake(class_basename($this));
    }

    /** {@inheritDoc} */
    public function cards()
    {
        return [
            new Help,
        ];
    }
}
