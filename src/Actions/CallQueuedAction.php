<?php

namespace Laravel\Nova\Actions;

use Illuminate\Bus\Batchable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Nova\Contracts\BatchableAction;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Nova;

#[\AllowDynamicProperties]
class CallQueuedAction
{
    use Batchable;
    use CallsQueuedActions;

    /**
     * Create a new job instance.
     */
    public function __construct(
        Action $action,
        string $method,
        ActionFields $fields,
        public EloquentCollection|Collection $models,
        string $actionBatchId
    ) {
        $this->action = $action;
        $this->method = $method;
        $this->fields = $fields;
        $this->models = $models;
        $this->actionBatchId = $actionBatchId;

        if (property_exists($action, 'timeout')) {
            /** @phpstan-ignore property.notFound */
            $this->timeout = $action->timeout;
        }

        if (property_exists($action, 'tries')) {
            /** @phpstan-ignore property.notFound */
            $this->tries = $action->tries;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->callAction(function ($action) {
            if ($action instanceof BatchableAction) {
                $action->withBatchId($this->batchId);
            }

            return $action->withActionBatchId($this->actionBatchId)
                        ->{$this->method}($this->fields, $this->models);
        });
    }

    /**
     * Call the failed method on the job instance.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function failed($e)
    {
        Nova::usingActionEvent(function ($actionEvent) use ($e) {
            if (! $this->action->withoutActionEvents) {
                $actionEvent->markBatchAsFailed($this->actionBatchId, $e);
            }
        });

        if ($method = $this->failedMethodName()) {
            \call_user_func([$this->action, $method], $this->fields, $this->models, $e);
        }
    }

    /**
     * Get the name of the "failed" method that should be called for the action.
     */
    protected function failedMethodName(): ?string
    {
        $method = $this->failedMethodForModel();

        if (method_exists($this->action, $method)) {
            return $method;
        }

        return method_exists($this->action, 'failed') ? 'failed' : null;
    }

    /**
     * Get the appropriate "failed" method name for the action's model type.
     */
    protected function failedMethodForModel(): ?string
    {
        return $this->models->isNotEmpty()
            ? 'failedFor'.Str::plural(class_basename($this->models->first()))
            : null;
    }
}
