<?php

namespace Laravel\Nova\Http\Controllers;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Nova\Actions\ActionEvent;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Util;
use Throwable;

class ResourceAttachController extends Controller
{
    use HandlesCustomRelationKeys;

    /**
     * The action event for the action.
     */
    protected ?ActionEvent $actionEvent = null;

    /**
     * Attach a related resource to the given resource.
     */
    public function __invoke(NovaRequest $request): Response
    {
        $resourceClass = $request->resource();

        $model = $request->findModelOrFail();

        tap(new $resourceClass($model), static function ($resource) use ($request) {
            abort_unless($resource->hasRelatableFieldOrRelationship($request, $request->viaRelationship), 404);
        });

        $this->validate($request, $model, $resourceClass);

        try {
            DB::connection($model->getConnectionName())->transaction(function () use ($request, $resourceClass, $model) {
                [$pivot, $callbacks] = $resourceClass::fillPivot(
                    $request,
                    $model,
                    $this->initializePivot(
                        $request,
                        $model->{$request->viaRelationship}()
                    )
                );

                tap(new $resourceClass($model), static function ($resource) use ($request) {
                    abort_unless($resource->authorizedToAttach($request, $request->findRelatedModelOrFail()), 401);
                });

                DB::transaction(function () use ($request, $model, $pivot) {
                    Nova::usingActionEvent(function ($actionEvent) use ($request, $model, $pivot) {
                        $this->actionEvent = $actionEvent->forAttachedResource($request, $model, $pivot);
                        $this->actionEvent->save();
                    });
                });

                $pivot->save();

                collect($callbacks)->each->__invoke();
            });

            return response()->noContent(200);
        } catch (Throwable $e) {
            optional($this->actionEvent)->delete();
            throw $e;
        }
    }

    /**
     * Validate the attachment request.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     */
    protected function validate(NovaRequest $request, $model, string $resourceClass): void
    {
        tap($this->creationRules($request, $resourceClass), function ($rules) use ($resourceClass, $request) {
            $attribute = $resourceClass::validationAttachableAttributeFor($request, $request->relatedResource);

            Validator::make($request->all(), $rules, [], $this->customRulesKeys($request, $attribute))->validate();

            $resourceClass::validateForAttachment($request);
        });
    }

    /**
     * Return the validation rules used for the request. Correctly aasign the rules used
     * to the main attribute if the user has defined a custom relation key.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     */
    protected function creationRules(NovaRequest $request, string $resourceClass): array
    {
        $rules = $resourceClass::creationRulesFor($request, $this->getRuleKey($request));

        if ($this->usingCustomRelationKey($request)) {
            $rules[$request->relatedResource] = $rules[$request->viaRelationship];
            unset($rules[$request->viaRelationship]);
        }

        return $rules;
    }

    /**
     * Initialize a fresh pivot model for the relationship.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\BelongsToMany  $relationship
     * @return (\Illuminate\Database\Eloquent\Model&\Illuminate\Database\Eloquent\Relations\Concerns\AsPivot)|\Illuminate\Database\Eloquent\Relations\Pivot
     *
     * @throws \RuntimeException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    protected function initializePivot(NovaRequest $request, $relationship): Model|Pivot
    {
        $parentKey = $request->resourceId;
        $relatedKey = $request->input($request->relatedResource);

        $parentKeyName = $relationship->getParentKeyName();
        $relatedKeyName = $relationship->getRelatedKeyName();

        if ($parentKeyName !== $request->model()->getKeyName()) {
            $parentKey = $request->findModelOrFail()->{$parentKeyName};
        }

        if ($relatedKeyName !== $request->newRelatedResource()::newModel()->getKeyName()) {
            $relatedKey = $request->findRelatedModelOrFail()->{$relatedKeyName};
        }

        $pivot = $relationship->newPivot($relationship->getDefaultPivotAttributes(), false);

        Util::expectPivotModel($pivot)->forceFill([
            $relationship->getForeignPivotKeyName() => $parentKey,
            $relationship->getRelatedPivotKeyName() => $relatedKey,
        ]);

        if ($relationship->withTimestamps) {
            $pivot->forceFill([
                $relationship->createdAt() => new DateTime,
                $relationship->updatedAt() => new DateTime,
            ]);
        }

        return $pivot;
    }
}
