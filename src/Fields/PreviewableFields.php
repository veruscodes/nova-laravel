<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Http\Requests\NovaRequest;

trait PreviewableFields
{
    /**
     * Indicates whether to show the field in the modal preview.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|object|array):(bool))|bool
     */
    public $showOnPreview = false;

    /**
     * Show the field in the modal preview.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|object|array):(bool))|bool  $callback
     * @return $this
     */
    public function showOnPreview(callable|bool $callback = true)
    {
        $this->showOnPreview = $callback;

        return $this;
    }

    /**
     * Specify that the element should only be shown on the preview modal.
     *
     * @return $this
     */
    public function onlyOnPreview()
    {
        $this->showOnIndex = false;
        $this->showOnDetail = false;
        $this->showOnCreation = false;
        $this->showOnUpdate = false;
        $this->showOnPreview = true;

        return $this;
    }

    /**
     * Determine if the field is to be shown in the preview modal.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|object|array  $resource
     */
    public function isShownOnPreview(NovaRequest $request, $resource): bool
    {
        if (\is_callable($this->showOnPreview)) {
            $this->showOnPreview = \call_user_func($this->showOnPreview, $request, $resource);
        }

        return $this->showOnPreview;
    }
}
