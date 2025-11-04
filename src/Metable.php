<?php

namespace Laravel\Nova;

use Laravel\Nova\Http\Requests\NovaRequest;

trait Metable
{
    /**
     * The meta data for the element.
     *
     * @var array<string, mixed>
     */
    public $meta = [];

    /**
     * Get additional meta information to merge with the element payload.
     *
     * @return array<string, mixed>
     */
    public function meta()
    {
        $request = app(NovaRequest::class);

        return collect($this->meta)
            ->map(static fn ($value) => value($value, $request))
            ->all();
    }

    /**
     * Set additional meta information for the element.
     *
     * @param  array<string, mixed>  $meta
     * @return $this
     */
    public function withMeta(array $meta)
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }
}
