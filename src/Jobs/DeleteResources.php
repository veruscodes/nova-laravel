<?php

namespace Laravel\Nova\Jobs;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Bus\Dispatchable;
use Laravel\Nova\Actions\Actionable;
use Laravel\Nova\Http\Requests\DeleteLensResourceRequest;
use Laravel\Nova\Http\Requests\DeleteResourceRequest;
use Laravel\Nova\Nova;

class DeleteResources
{
    use DeletesFields;
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @param  class-string<\Laravel\Nova\Resource>|null  $resourceClass
     */
    public function __construct(
        public DeleteResourceRequest|DeleteLensResourceRequest $request,
        public ?string $resourceClass = null
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->request->chunks(150, function ($models) {
            $models->each(function ($model) {
                $this->deleteFields($this->request, $model);

                $uses = class_uses_recursive($model);

                if (\in_array(Actionable::class, $uses) && ! \in_array(SoftDeletes::class, $uses)) {
                    $model->actions()->delete();
                }

                if (! \is_null($this->resourceClass)) {
                    $this->resourceClass::beforeDelete($this->request, $model);
                }

                $model->delete();

                if (! \is_null($this->resourceClass)) {
                    $this->resourceClass::afterDelete($this->request, $model);
                }

                Nova::usingActionEvent(function ($actionEvent) use ($model) {
                    $actionEvent->insert(
                        $actionEvent->forResourceDelete(Nova::user($this->request), collect([$model]))
                            ->map->getAttributes()->all()
                    );
                });
            });
        });
    }
}
