<?php

namespace Laravel\Nova\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use Laravel\Nova\Contracts\Deletable;
use Laravel\Nova\DeleteField;
use Laravel\Nova\Http\Requests\DetachResourceRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;

class DetachResources
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public DetachResourceRequest $request
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $parent = tap($this->request->findParentResourceOrFail(), function ($resource) {
            abort_unless($resource->hasRelatableFieldOrRelationship($this->request, $this->request->viaRelationship), 409);
        })->model();

        $relation = $parent->{$this->request->viaRelationship}();

        $accessor = $relation->getPivotAccessor();

        /** @var string $accessorKeyName */
        /** @phpstan-ignore argument.templateType */
        $accessorKeyName = transform(
            $relation->getPivotClass(), fn ($pivotClass) => (new $pivotClass)->getKeyName()
        );

        $inPivots = $this->request->resources !== 'all' ? $this->request->pivots : null;

        $this->request->chunks(150, function ($models) use ($accessor, $accessorKeyName, $inPivots, $parent) {
            foreach ($models as $model) {
                $pivot = $model->{$accessor};

                if (empty($inPivots) || \in_array($pivot->getAttribute($accessorKeyName), $inPivots)) {
                    $this->deletePivot(
                        $this->request, $pivot, $model, $parent
                    );
                }
            }
        });
    }

    /**
     * Delete pivot relations from model.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Pivot  $pivot
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     */
    protected function deletePivot(DetachResourceRequest $request, $pivot, $model, $parent): void
    {
        $this->deletePivotFields(
            $request, $request->newResourceWith($model), $pivot
        );

        $pivot->delete();

        Nova::usingActionEvent(static function ($actionEvent) use ($pivot, $model, $parent, $request) {
            $actionEvent->insert(
                $actionEvent->forResourceDetach(
                    Nova::user($request), $parent, collect([$model]), $pivot->getMorphClass()
                )->map->getAttributes()->all()
            );
        });
    }

    /**
     * Delete the pivot fields on the given pivot model.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Pivot  $pivot
     */
    protected function deletePivotFields(DetachResourceRequest $request, Resource $resource, $pivot): void
    {
        $resource->resolvePivotFields($request, $request->viaResource)
            ->whereInstanceOf(Deletable::class)
            ->filter->isPrunable()
            ->each(static function ($field) use ($request, $pivot) {
                /** @var \Laravel\Nova\Fields\Field&\Laravel\Nova\Contracts\Deletable $field */
                DeleteField::forRequest($request, $field, $pivot)->save();
            });
    }
}
