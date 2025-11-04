<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionCollection;
use Laravel\Nova\Http\Requests\ActionRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;

class ActionController extends Controller
{
    /**
     * List the actions for the given resource.
     */
    public function index(NovaRequest $request): JsonResponse
    {
        $resourceId = with($request->input('resources'), static function ($resourceIds) {
            return \is_array($resourceIds) && \count($resourceIds) === 1 ? $resourceIds[0] : null;
        });

        $resource = $request->newResourceWith(
            $request->findModel($resourceId)
        );

        return response()->json(with([
            'actions' => $this->availableActions($request, $resource),
            'pivotActions' => [
                'name' => Nova::humanize($request->pivotName()),
                'actions' => $resource->availablePivotActions($request),
            ],
        ], static function ($payload) use ($resource, $request) {
            $actionCounts = ($request->display !== 'detail' ? $payload['actions'] : $resource->resolveActions($request))->countsByTypeOnIndex();
            $pivotActionCounts = ActionCollection::make($payload['pivotActions']['actions'])->countsByTypeOnIndex();

            $payload['counts'] = [
                'sole' => $actionCounts['sole'] + $pivotActionCounts['sole'],
                'standalone' => $actionCounts['standalone'] + $pivotActionCounts['standalone'],
                'resource' => $actionCounts['resource'] + $pivotActionCounts['resource'],
            ];

            return $payload;
        }));
    }

    /**
     * Perform an action on the specified resources.
     */
    public function store(ActionRequest $request): mixed
    {
        $request->validateFields();

        return $request->action()->handleRequest($request);
    }

    /**
     * Sync an action field on the specified resources.
     */
    public function sync(ActionRequest $request): JsonResponse
    {
        $action = $this->availableActions($request, $request->newResource())
            ->first(static fn ($action) => $action->uriKey() === $request->query('action'));

        abort_unless($action instanceof Action, 404);

        return response()->json(
            collect($action->fields($request))
                ->filter(static function ($field) use ($request) {
                    return $request->query('field') === $field->attribute &&
                        $request->query('component') === $field->dependentComponentKey();
                })->each->syncDependsOn($request)
                ->first()
        );
    }

    /**
     * Get the available actions for the request.
     *
     * @return \Laravel\Nova\Actions\ActionCollection<int, \Laravel\Nova\Actions\Action>
     */
    protected function availableActions(NovaRequest $request, Resource $resource): ActionCollection
    {
        $method = match ($request->display) {
            'index' => 'availableActionsOnIndex',
            'detail' => 'availableActionsOnDetail',
            default => 'availableActions',
        };

        return $resource->{$method}($request);
    }
}
