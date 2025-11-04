<?php

namespace Laravel\Nova\Actions;

use Closure;
use Illuminate\Bus\PendingBatch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Laravel\Nova\Contracts\BatchableAction;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\ActionRequest;
use Laravel\Nova\Nova;

class DispatchAction
{
    /**
     * The pending batch instance (if the action implements BatchableAction).
     */
    protected ?PendingBatch $batchJob = null;

    /**
     * Set dispatchable callback.
     *
     * @var (callable(\Laravel\Nova\Actions\Response):(mixed))|null
     */
    protected $dispatchableCallback;

    /**
     * Create a new action dispatcher instance.
     */
    public function __construct(
        protected ActionRequest $request,
        protected Action $action,
        protected ActionFields $fields
    ) {
        if ($action instanceof BatchableAction) {
            $this->configureBatchJob($action, $fields);
        }
    }

    /**
     * Configure the batch job for the action.
     */
    protected function configureBatchJob(Action $action, ActionFields $fields): void
    {
        $this->batchJob = tap(Bus::batch([]), function (PendingBatch $batch) use ($action, $fields) {
            $batch->name($action->name());

            if (! \is_null($connection = $this->connection())) {
                $batch->onConnection($connection);
            }

            if (! \is_null($queue = $this->queue())) {
                $batch->onQueue($queue);
            }

            $action->withBatch($fields, $batch);
        });
    }

    /**
     * Dispatch the action.
     *
     * @throws \Throwable
     */
    public function dispatch(): Response
    {
        if ($this->action instanceof ShouldQueue) {
            return tap(new Response, function (Response $response) {
                with($response, $this->dispatchableCallback);

                if (! \is_null($this->batchJob)) {
                    $this->batchJob->dispatch();
                }

                return $response->successful();
            });
        }

        return with(new Response, $this->dispatchableCallback);
    }

    /**
     * Dispatch the given action.
     *
     * @return $this
     */
    public function handleStandalone(string $method)
    {
        $this->dispatchableCallback = function (Response $response) use ($method) {
            if ($this->action instanceof ShouldQueue) {
                $this->addQueuedActionJob($method, collect());

                return;
            }

            return $response->successful([
                $this->dispatchSynchronouslyForCollection($method, collect()),
            ]);
        };

        return $this;
    }

    /**
     * Dispatch the given action.
     *
     * @return $this
     */
    public function handleRequest(ActionRequest $request, string $method, int $chunkCount)
    {
        $this->dispatchableCallback = function (Response $response) use ($request, $method, $chunkCount) {
            if ($this->action instanceof ShouldQueue) {
                $request->chunks($chunkCount, fn ($models) => $this->forModels($method, $models->filterForExecution($request)));

                return;
            }

            $wasExecuted = false;

            $results = $request->chunks(
                $chunkCount, function ($models) use ($request, $method, &$wasExecuted) {
                    $models = $models->filterForExecution($request);

                    if (\count($models) > 0) {
                        $wasExecuted = true;
                    }

                    return $this->forModels($method, $models);
                }
            );

            return $wasExecuted ? $response->successful($results) : $response->failed();
        };

        return $this;
    }

    /**
     * Dispatch the given action using custom handler.
     *
     * @param  \Closure(\Laravel\Nova\Http\Requests\ActionRequest, \Laravel\Nova\Actions\Response, \Laravel\Nova\Fields\ActionFields):\Laravel\Nova\Actions\Response  $callback
     * @return $this
     */
    public function handleUsing(ActionRequest $request, Closure $callback)
    {
        $this->dispatchableCallback = fn (Response $response) => \call_user_func($callback, $request, $response, $this->fields);

        return $this;
    }

    /**
     * Dispatch the given action.
     *
     * @return mixed|void
     *
     * @throws \Throwable
     */
    public function forModels(string $method, Collection $models)
    {
        if ($this->action->isStandalone() || $models->isEmpty()) {
            return;
        }

        if ($this->action instanceof ShouldQueue) {
            $this->addQueuedActionJob($method, $models);

            return;
        }

        return $this->dispatchSynchronouslyForCollection($method, $models);
    }

    /**
     * Dispatch the given action synchronously for a model collection.
     *
     * @throws \Throwable
     */
    protected function dispatchSynchronouslyForCollection(string $method, Collection $models): mixed
    {
        return Transaction::run(function (string $batchId) use ($method, $models) {
            Nova::usingActionEvent(function ($actionEvent) use ($batchId, $models) {
                if (! $this->action->withoutActionEvents) {
                    $actionEvent->createForModels(
                        $this->request, $this->action, $batchId, $models
                    );
                }
            });

            return $this->action->withActionBatchId($batchId)->{$method}($this->fields, $models);
        }, function ($batchId) {
            Nova::usingActionEvent(function ($actionEvent) use ($batchId) {
                if (! $this->action->withoutActionEvents) {
                    $actionEvent->markBatchAsFinished($batchId);
                }
            });
        });
    }

    /**
     * Dispatch the given action to the queue for a model collection.
     *
     * @throws \Throwable
     */
    protected function addQueuedActionJob(string $method, Collection $models): mixed
    {
        return Transaction::run(function (string $batchId) use ($method, $models) {
            Nova::usingActionEvent(function ($actionEvent) use ($batchId, $models) {
                if (! $this->action->withoutActionEvents) {
                    $actionEvent->createForModels(
                        $this->request, $this->action, $batchId, $models, 'waiting'
                    );
                }
            });

            $job = new CallQueuedAction(
                $this->action, $method, $this->request->resolveFields(), $models, $batchId
            );

            if ($this->action instanceof BatchableAction) {
                $this->batchJob->add([$job]);

                $this->batchJob->options['resourceIds'] = array_values(array_unique(array_merge(
                    $this->batchJob->options['resourceIds'] ?? [],
                    $models->map(fn ($model) => $model->getKey())->all()
                )));
            } else {
                Queue::connection($this->connection())->pushOn(
                    $this->queue(), $job
                );
            }
        });
    }

    /**
     * Extract the queue connection for the action.
     */
    protected function connection(): ?string
    {
        return property_exists($this->action, 'connection') ? $this->action->connection : null;
    }

    /**
     * Extract the queue name for the action.
     */
    protected function queue(): ?string
    {
        return property_exists($this->action, 'queue') ? $this->action->queue : null;
    }
}
