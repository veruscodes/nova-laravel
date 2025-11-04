<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Actions\ActionEvent;
use Laravel\Nova\Exceptions\ResourceSaveCancelledException;
use Laravel\Nova\Http\Requests\UpdateResourceRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\URL;
use Laravel\Nova\Util;
use Throwable;

class ResourceUpdateController extends Controller
{
    /**
     * The action event for the action.
     */
    protected ?ActionEvent $actionEvent = null;

    /**
     * Create a new resource.
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    public function __invoke(UpdateResourceRequest $request): JsonResponse
    {
        $model = $request->findModelQuery()->lockForUpdate()->firstOrFail();

        try {
            [$model, $resource] = DB::connection($model->getConnectionName())->transaction(function () use ($request, $model) {
                $resource = $request->newResourceWith($model);
                $resource->authorizeToUpdate($request);
                $resource::validateForUpdate($request, $resource);

                if ($this->modelHasBeenUpdatedSinceRetrieval($request, $model)) {
                    response('', 409)->throwResponse();
                }

                [$model, $callbacks] = $resource::fillForUpdate($request, $model);

                DB::transaction(function () use ($request, $model) {
                    Nova::usingActionEvent(function ($actionEvent) use ($request, $model) {
                        $this->actionEvent = $actionEvent->forResourceUpdate(Nova::user($request), $model);
                        $this->actionEvent->save();
                    });
                });

                $resource::beforeUpdate($request, $model);

                if ($model->save() === false) {
                    throw new ResourceSaveCancelledException;
                }

                collect($callbacks)->each->__invoke();

                $resource::afterUpdate($request, $model);

                return [$model, $resource];
            });

            tap(Nova::user($request), static function ($user) use ($model) {
                if ($model::class === Util::userModel() && $model->is($user)) {
                    $user->refresh();
                }
            });

            return response()->json([
                'id' => $model->getKey(),
                'redirect' => URL::make($resource::redirectAfterUpdate($request, $resource)),
            ]);
        } catch (Throwable $e) {
            optional($this->actionEvent)->delete();
            throw $e;
        }
    }

    /**
     * Determine if the model has been updated since it was retrieved.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    protected function modelHasBeenUpdatedSinceRetrieval(UpdateResourceRequest $request, $model): bool
    {
        $resource = $request->newResource();

        // Check to see whether Traffic Cop is enabled for this resource...
        if ($resource::trafficCop($request) === false) {
            return false;
        }

        $column = $model->getUpdatedAtColumn();

        if (! ($model->usesTimestamps() && $model->{$column})) {
            return false;
        }

        return $request->input('_retrieved_at') && $model->{$column}->gt(
            Carbon::createFromTimestamp($request->input('_retrieved_at'))
        );
    }
}
