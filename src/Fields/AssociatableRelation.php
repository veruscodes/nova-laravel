<?php

namespace Laravel\Nova\Fields;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Nova\Contracts\QueryBuilder;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\TrashedStatus;

trait AssociatableRelation
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
    public $reordersOnAssociatableCallback = true;

    /**
     * Determine if the display values should be automatically sorted when rendering associatable relation.
     */
    public function shouldReorderAssociatableValues(NovaRequest $request): bool
    {
        if (\is_callable($this->reordersOnAssociatableCallback)) {
            return \call_user_func($this->reordersOnAssociatableCallback, $request);
        }

        return $this->reordersOnAssociatableCallback;
    }

    /**
     * Determine reordering on associatables.
     *
     * @return $this
     */
    public function dontReorderAssociatables()
    {
        $this->reordersOnAssociatableCallback = false;

        return $this;
    }

    /**
     * Determine reordering on associatables.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):bool)|bool  $value
     * @return $this
     */
    public function reorderAssociatables(callable|bool $value = true)
    {
        $this->reordersOnAssociatableCallback = $value;

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
    protected function applyAssociatableCallbacks(Builder $query, NovaRequest $request, string $resourceClass, Model $model)
    {
        if (\is_callable($this->relatableQueryCallback)) {
            \call_user_func($this->relatableQueryCallback, $request, $query);

            return;
        }

        forward_static_call($this->relatableQueryCallable($request, $model, $resourceClass), $request, $query, $this);
    }

    /**
     * Get the relatableQuery callable.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     * @return array
     */
    protected function relatableQueryCallable(NovaRequest $request, $model, string $resourceClass)
    {
        return ($method = $this->relatableQueryMethod($request, $model))
            ? [$request->resource(), $method]
            : [$resourceClass, 'relatableQuery'];
    }

    /**
     * Get the relatableQuery method name.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return string|null
     */
    protected function relatableQueryMethod(NovaRequest $request, $model)
    {
        $method = 'relatable'.Str::plural(class_basename($model));

        return method_exists($request->resource(), $method) ? $method : null;
    }

    /**
     * Build an associatable query for the field.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     */
    public function searchAssociatableQuery(NovaRequest $request, string $resourceClass, bool $withTrashed = false): QueryBuilder
    {
        $model = $resourceClass::newModel();

        $query = app()->make(QueryBuilder::class, [$resourceClass]);

        $request->first === 'true'
            ? $query->whereKey($model->newQueryWithoutScopes(), $request->current)
            : $query->search(
                $request, $model->newQuery(), $request->search,
                [], [], TrashedStatus::fromBoolean($withTrashed)
            );

        return $query->tap(function (Builder $query) use ($request, $resourceClass, $model) {
            $this->applyAssociatableCallbacks($query, $request, $resourceClass, $model);
        });
    }

    /**
     * Build an associatable query for the field.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     */
    public function buildAssociatableQuery(NovaRequest $request, string $resourceClass, bool $withTrashed = false): QueryBuilder
    {
        $model = $resourceClass::newModel();

        /** @var QueryBuilder $query */
        $query = app()->make(QueryBuilder::class, [$resourceClass]);

        return $query->search($request, $model->newQuery(), null, [], [], TrashedStatus::fromBoolean($withTrashed))
            ->tap(function (Builder $query) use ($request, $resourceClass, $model) {
                $this->applyAssociatableCallbacks($query, $request, $resourceClass, $model);
            });
    }
}
