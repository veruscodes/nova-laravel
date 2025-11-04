<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Element;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

/**
 * @phpstan-type TMixedResource \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|object|array
 */
abstract class FieldElement extends Element
{
    /**
     * The field's assigned panel.
     *
     * @var \Laravel\Nova\Panel|null
     */
    public $panel = null;

    /**
     * Indicates if the element should be shown on the index view.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest, mixed):(bool))|bool
     *
     * @phpstan-var (callable(\Laravel\Nova\Http\Requests\NovaRequest, TMixedResource):(bool))|bool
     */
    public $showOnIndex = true;

    /**
     * Indicates if the element should be shown on the detail view.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest, mixed):(bool))|bool
     *
     * @phpstan-var (callable(\Laravel\Nova\Http\Requests\NovaRequest, TMixedResource):(bool))|bool
     */
    public $showOnDetail = true;

    /**
     * Indicates if the element should be shown on the creation view.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool
     */
    public $showOnCreation = true;

    /**
     * Indicates if the element should be shown on the update view.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest, mixed):(bool))|bool
     */
    public $showOnUpdate = true;

    /**
     * Specify that the element should be hidden from the index view.
     *
     * @param  (callable():(bool))|bool  $callback
     * @return $this
     */
    public function hideFromIndex(callable|bool $callback = true)
    {
        $this->showOnIndex = \is_callable($callback) ? static function () use ($callback) {
            return ! \call_user_func_array($callback, \func_get_args());
        }
        : ! $callback;

        return $this;
    }

    /**
     * Specify that the element should be hidden from the detail view.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest, mixed):(bool))|bool  $callback
     * @return $this
     */
    public function hideFromDetail(callable|bool $callback = true)
    {
        $this->showOnDetail = \is_callable($callback) ? static function () use ($callback) {
            return ! \call_user_func_array($callback, \func_get_args());
        }
        : ! $callback;

        return $this;
    }

    /**
     * Specify that the element should be hidden from the creation view.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool  $callback
     * @return $this
     */
    public function hideWhenCreating(callable|bool $callback = true)
    {
        $this->showOnCreation = \is_callable($callback) ? static function () use ($callback) {
            return ! \call_user_func_array($callback, \func_get_args());
        }
        : ! $callback;

        return $this;
    }

    /**
     * Specify that the element should be hidden from the update view.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest, mixed):(bool))|bool  $callback
     * @return $this
     */
    public function hideWhenUpdating(callable|bool $callback = true)
    {
        $this->showOnUpdate = \is_callable($callback) ? static function () use ($callback) {
            return ! \call_user_func_array($callback, \func_get_args());
        }
        : ! $callback;

        return $this;
    }

    /**
     * Specify that the element should be visible on the index view.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest, mixed):(bool))|bool  $callback
     *
     * @phpstan-param (callable(\Laravel\Nova\Http\Requests\NovaRequest, TMixedResource):(bool))|bool  $callback
     *
     * @return $this
     */
    public function showOnIndex(callable|bool $callback = true)
    {
        $this->showOnIndex = $callback;

        return $this;
    }

    /**
     * Specify that the element should be hidden from the detail view.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest, mixed):(bool))|bool  $callback
     *
     * @phpstan-param (callable(\Laravel\Nova\Http\Requests\NovaRequest, TMixedResource):(bool))|bool  $callback
     *
     * @return $this
     */
    public function showOnDetail(callable|bool $callback = true)
    {
        $this->showOnDetail = $callback;

        return $this;
    }

    /**
     * Specify that the element should be hidden from the creation view.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool  $callback
     * @return $this
     */
    public function showOnCreating(callable|bool $callback = true)
    {
        $this->showOnCreation = $callback;

        return $this;
    }

    /**
     * Specify that the element should be hidden from the update view.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest, mixed):(bool))|bool  $callback
     * @return $this
     */
    public function showOnUpdating(callable|bool $callback = true)
    {
        $this->showOnUpdate = $callback;

        return $this;
    }

    /**
     * Check for showing when updating.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|object|array  $resource
     */
    public function isShownOnUpdate(NovaRequest $request, $resource): bool
    {
        if (\is_callable($this->showOnUpdate)) {
            $this->showOnUpdate = \call_user_func($this->showOnUpdate, $request, $resource);
        }

        return $this->showOnUpdate;
    }

    /**
     * Check showing on index.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|object|array  $resource
     */
    public function isShownOnIndex(NovaRequest $request, $resource): bool
    {
        if (\is_callable($this->showOnIndex)) {
            $this->showOnIndex = \call_user_func($this->showOnIndex, $request, $resource);
        }

        return $this->showOnIndex;
    }

    /**
     * Determine if the field is to be shown on the detail view.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|object|array  $resource
     */
    public function isShownOnDetail(NovaRequest $request, $resource): bool
    {
        if (\is_callable($this->showOnDetail)) {
            $this->showOnDetail = \call_user_func($this->showOnDetail, $request, $resource);
        }

        return $this->showOnDetail;
    }

    /**
     * Check for showing when creating.
     */
    public function isShownOnCreation(NovaRequest $request): bool
    {
        if (\is_callable($this->showOnCreation)) {
            $this->showOnCreation = \call_user_func($this->showOnCreation, $request);
        }

        return $this->showOnCreation;
    }

    /**
     * Specify that the element should only be shown on the index view.
     *
     * @return $this
     */
    public function onlyOnIndex()
    {
        $this->showOnIndex = true;
        $this->showOnDetail = false;
        $this->showOnCreation = false;
        $this->showOnUpdate = false;

        return $this;
    }

    /**
     * Specify that the element should only be shown on the detail view.
     *
     * @return $this
     */
    #[\Override]
    public function onlyOnDetail()
    {
        parent::onlyOnDetail();

        $this->showOnIndex = false;
        $this->showOnDetail = true;
        $this->showOnCreation = false;
        $this->showOnUpdate = false;

        return $this;
    }

    /**
     * Specify that the element should only be shown on forms.
     *
     * @return $this
     */
    public function onlyOnForms()
    {
        $this->showOnIndex = false;
        $this->showOnDetail = false;
        $this->showOnCreation = true;
        $this->showOnUpdate = true;

        return $this;
    }

    /**
     * Specify that the element should be hidden from forms.
     *
     * @return $this
     */
    public function exceptOnForms()
    {
        $this->showOnIndex = true;
        $this->showOnDetail = true;
        $this->showOnCreation = false;
        $this->showOnUpdate = false;

        return $this;
    }

    /**
     * Prepare the field element for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'panel' => $this->panel?->name,
        ]);
    }
}
