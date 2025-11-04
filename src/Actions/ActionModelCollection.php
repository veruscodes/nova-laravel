<?php

namespace Laravel\Nova\Actions;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Laravel\Nova\Http\Requests\ActionRequest;
use Laravel\Nova\Resource;

/**
 * @template TKey of array-key
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Collection<TKey, TModel>
 */
class ActionModelCollection extends EloquentCollection
{
    /**
     * Remove models the user does not have permission to execute the action against.
     *
     * @return static<TKey, TModel>
     */
    public function filterForExecution(ActionRequest $request): static
    {
        $action = $request->action();
        $isPivotAction = $request->isPivotAction();

        /** @phpstan-ignore return.type */
        return new static($this->filter(function ($model) use ($request, $action, $isPivotAction) {
            if ($isPivotAction || $action->runCallback) {
                return $action->authorizedToRun($request, $model);
            }

            return $action->authorizedToRun($request, $model) && $this->filterByResourceAuthorization($request, $request->newResourceWith($model), $action);
        }));
    }

    /**
     * Remove models the user does not have permission to execute the action against.
     *
     * @param  \Laravel\Nova\Actions\Action|\Laravel\Nova\Actions\DestructiveAction  $action
     */
    protected function filterByResourceAuthorization(ActionRequest $request, Resource $resource, Action $action): bool
    {
        return $resource->authorizedToRunAction($request, $action);
    }
}
