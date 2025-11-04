<?php

namespace Laravel\Nova\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use Laravel\Nova\Http\Requests\RestoreLensResourceRequest;
use Laravel\Nova\Http\Requests\RestoreResourceRequest;
use Laravel\Nova\Nova;

class RestoreResources
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @param  class-string<\Laravel\Nova\Resource>|null  $resourceClass
     */
    public function __construct(
        public RestoreResourceRequest|RestoreLensResourceRequest $request,
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
                if (! \is_null($this->resourceClass)) {
                    $this->resourceClass::beforeRestore($this->request, $model);
                }

                /** @phpstan-ignore method.notFound */
                $model->restore();

                if (! \is_null($this->resourceClass)) {
                    $this->resourceClass::afterRestore($this->request, $model);
                }

                Nova::usingActionEvent(function ($actionEvent) use ($model) {
                    $actionEvent->insert(
                        $actionEvent->forResourceRestore(Nova::user($this->request), collect([$model]))
                            ->map->getAttributes()->all()
                    );
                });
            });
        });
    }
}
