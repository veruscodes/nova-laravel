<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionCollection;
use Laravel\Nova\Http\Requests\LensActionRequest;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Nova;

class LensActionController extends Controller
{
    /**
     * List the actions for the given resource.
     */
    public function index(LensRequest $request): JsonResponse
    {
        $lens = $request->lens();

        return response()->json(with([
            'actions' => $lens->availableActionsOnIndex($request),
            'pivotActions' => [
                'name' => Nova::humanize($request->pivotName()),
                'actions' => $lens->availablePivotActions($request),
            ],
            'counts' => $lens->resolveActions($request)->countsByTypeOnIndex(),
        ], static function ($payload) use ($lens, $request) {
            $actionCounts = $lens->resolveActions($request)->countsByTypeOnIndex();
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
    public function store(LensActionRequest $request): mixed
    {
        $request->validateFields();

        return $request->action()->handleRequest($request);
    }

    /**
     * Sync an action field on the specified resources.
     */
    public function sync(LensActionRequest $request): JsonResponse
    {
        $action = $request->lens()->availableActions($request)
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
}
