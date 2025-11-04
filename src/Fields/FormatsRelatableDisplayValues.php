<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Nova;
use Laravel\Nova\Resource;

use function Orchestra\Sidekick\is_safe_callable;

trait FormatsRelatableDisplayValues
{
    /**
     * The column that should be displayed for the field.
     *
     * @var (callable(mixed):(string))|null
     */
    public $display;

    /**
     * Format the associatable display value.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model  $resource
     */
    protected function formatDisplayValue($resource): string
    {
        if (! $resource instanceof Resource) {
            $resource = Nova::newResourceFromModel($resource);
        }

        if (\is_callable($this->display)) {
            return \call_user_func($this->display, $resource);
        }

        return $resource->title();
    }

    /**
     * Set the column that should be displayed for the field.
     *
     * @param  (callable(mixed):(string))|string  $display
     * @return $this
     */
    public function display(callable|string $display)
    {
        $this->display = is_safe_callable($display)
            ? $display
            : fn ($resource) => $resource->{$display};

        return $this;
    }
}
