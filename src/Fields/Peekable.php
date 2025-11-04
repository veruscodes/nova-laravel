<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

trait Peekable
{
    /**
     * Indicates if the related resource can be peeked at.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool|null
     */
    public $peekable = true;

    /**
     * Specify if the related resource can be peeked at.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool  $callback
     * @return $this
     */
    public function peekable(callable|bool $callback = true)
    {
        $this->peekable = $callback;

        return $this;
    }

    /**
     * Prevent the user from peeking at the related resource.
     *
     * @return $this
     */
    public function noPeeking()
    {
        $this->peekable = false;

        return $this;
    }

    /**
     * Resolve whether the relation is able to be peeked at.
     */
    public function isPeekable(NovaRequest $request): bool
    {
        if (\is_callable($this->peekable)) {
            $this->peekable = \call_user_func($this->peekable, $request);
        }

        return $this->peekable;
    }

    /**
     * Determine if the relation has fields that can be peeked at.
     */
    public function hasFieldsToPeekAt(NovaRequest $request): bool
    {
        if (! $request->isPresentationRequest() && ! $request->isResourcePreviewRequest()) {
            return false;
        }

        if (\is_null($relatedResource = $this->relatedResource())) {
            return false;
        }

        return $relatedResource->peekableFieldsCount($request) > 0;
    }

    /**
     * Return the appropriate related Resource for the field.
     */
    protected function relatedResource(): ?Resource
    {
        if ($this instanceof MorphTo) {
            return $this->morphToResource;
        }

        /** @phpstan-ignore property.notFound */
        return $this->belongsToResource;
    }
}
