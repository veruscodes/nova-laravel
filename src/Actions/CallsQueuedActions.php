<?php

namespace Laravel\Nova\Actions;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Nova;

/**
 * @internal
 */
trait CallsQueuedActions
{
    use Batchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The action class name.
     */
    public Action $action;

    /**
     * The method that should be called on the action.
     */
    public string $method;

    /**
     * The resolved fields.
     */
    public ActionFields $fields;

    /**
     * The batch ID of the action event records.
     */
    public string $actionBatchId;

    /**
     * Call the action using the given callback.
     *
     * @param  callable(\Laravel\Nova\Actions\Action):void  $callback
     */
    protected function callAction(callable $callback): void
    {
        Nova::usingActionEvent(function ($actionEvent) {
            if (! $this->action->withoutActionEvents) {
                $actionEvent->markBatchAsRunning($this->actionBatchId);
            }
        });

        $action = $this->setJobInstanceIfNecessary($this->action);

        $callback($action);

        if (! $this->job->hasFailed() && ! $this->job->isReleased()) {
            Nova::usingActionEvent(function ($actionEvent) {
                if (! $this->action->withoutActionEvents) {
                    $actionEvent->markBatchAsFinished($this->actionBatchId);
                }
            });
        }
    }

    /**
     * Set the job instance of the given class if necessary.
     *
     * @param  mixed  $instance
     * @return mixed
     */
    protected function setJobInstanceIfNecessary($instance)
    {
        if (\in_array(InteractsWithQueue::class, class_uses_recursive($instance::class))) {
            $instance->setJob($this->job);
        }

        return $instance;
    }

    /**
     * Get the display name for the queued job.
     */
    public function displayName(): string
    {
        return $this->action::class;
    }
}
