<?php

namespace Laravel\Nova\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use Laravel\Nova\Actions\Actionable;
use Laravel\Nova\Http\Requests\ForceDeleteLensResourceRequest;
use Laravel\Nova\Http\Requests\ForceDeleteResourceRequest;
use Laravel\Nova\Nova;

class ForceDeleteResources
{
    use DeletesFields;
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @param  class-string<\Laravel\Nova\Resource>|null  $resourceClass
     */
    public function __construct(
        public ForceDeleteResourceRequest|ForceDeleteLensResourceRequest $request,
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
            /** @var \Illuminate\Database\Eloquent\Collection<array-key, \Illuminate\Database\Eloquent\Model> $models */
            $models->each(function ($model) {
                $this->forceDeleteFields($this->request, $model);

                if (\in_array(Actionable::class, class_uses_recursive($model))) {
                    /** @phpstan-ignore method.notFound */
                    $model->actions()->delete();
                }

                if (! \is_null($this->resourceClass)) {
                    $this->resourceClass::beforeForceDelete($this->request, $model);
                }

                $model->forceDelete();

                if (! \is_null($this->resourceClass)) {
                    $this->resourceClass::afterForceDelete($this->request, $model);
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
