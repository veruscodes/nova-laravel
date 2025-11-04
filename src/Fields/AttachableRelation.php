<?php

namespace Laravel\Nova\Fields;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Nova\Contracts\QueryBuilder;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;
use Laravel\Nova\TrashedStatus;

trait AttachableRelation
{
    /**
     * The callback that should be run to associate relations.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Contracts\Database\Eloquent\Builder):(\Illuminate\Contracts\Database\Eloquent\Builder))|null
     */
    public $relatableQueryCallback;

    /**
     * Determines if the display values should be automatically sorted.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool
     */
    public $reordersOnAttachableCallback = true;

    /**
     * Determine if the display values should be automatically sorted when rendering attachable relation.
     */
    public function shouldReorderAttachableValues(NovaRequest $request): bool
    {
        if (\is_callable($this->reordersOnAttachableCallback)) {
            return \call_user_func($this->reordersOnAttachableCallback, $request);
        }

        return $this->reordersOnAttachableCallback;
    }

    /**
     * Determine reordering on attachables.
     *
     * @return $this
     */
    public function dontReorderAttachables()
    {
        $this->reordersOnAttachableCallback = false;

        return $this;
    }

    /**
     * Determine reordering on attachables.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool  $value
     * @return $this
     */
    public function reorderAttachables(callable|bool $value = true)
    {
        $this->reordersOnAttachableCallback = $value;

        return $this;
    }

    /**
     * Determine the associate relations query.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest, \Illuminate\Contracts\Database\Eloquent\Builder):(\Illuminate\Contracts\Database\Eloquent\Builder))|null  $callback
     * @return $this
     */
    public function relatableQueryUsing(?callable $callback)
    {
        $this->relatableQueryCallback = $callback;

        return $this;
    }

    /**
     * Applies the relatableQueryCallback if applicable or fallbacks to calling relateQuery method on related resource.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     * @return void
     */
    protected function applyAttachableCallbacks(Builder $query, NovaRequest $request, string $resourceClass, Model $model)
    {
        if (\is_callable($this->relatableQueryCallback)) {
            \call_user_func($this->relatableQueryCallback, $request, $query);

            return;
        }

        forward_static_call($this->attachableQueryCallable($request, $model, $resourceClass), $request, $query, $this);
    }

    /**
     * Build an attachable query for the field.
     */
    public function buildAttachableQuery(NovaRequest $request, bool $withTrashed = false): QueryBuilder
    {
        $model = forward_static_call([$resourceClass = $this->resourceClass, 'newModel']);

        $query = app()->make(QueryBuilder::class, [$resourceClass]);

        $request->first === 'true'
            ? $query->whereKey($model->newQueryWithoutScopes(), $request->current)
            : $query->search(
                $request, $model->newQuery(), $request->search,
                [], [], TrashedStatus::fromBoolean($withTrashed)
            );

        return $query->tap(function ($query) use ($request, $model) {
            $this->applyAttachableCallbacks($query, $request, $this->resourceClass, $model);
        });
    }

    /**
     * Get the attachable query method name.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     */
    protected function attachableQueryCallable(NovaRequest $request, $model, string $resourceClass): callable
    {
        return ($method = $this->attachableQueryMethod($request, $model))
            ? [$request->resource(), $method]
            : [$resourceClass, 'relatableQuery'];
    }

    /**
     * Get the attachable query method name.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    protected function attachableQueryMethod(NovaRequest $request, $model): ?string
    {
        $method = 'relatable'.Str::plural(class_basename($model));

        return method_exists($request->resource(), $method) ? $method : null;
    }

    /**
     * Format the given attachable resource.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model  $resource
     */
    public function formatAttachableResource(NovaRequest $request, $resource): array
    {
        if (! $resource instanceof Resource) {
            $resource = Nova::newResourceFromModel($resource);
        }

        return array_filter([
            'avatar' => $resource->resolveAvatarUrl($request),
            'display' => $this->formatDisplayValue($resource),
            'value' => optional(ID::forResource($resource))->value ?? $resource->getKey(),
            'subtitle' => $resource->subtitle(),
        ]);
    }
}
