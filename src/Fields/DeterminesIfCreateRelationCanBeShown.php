<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Http\Requests\NovaRequest;

trait DeterminesIfCreateRelationCanBeShown
{
    /**
     * The callback used to determine if the create relation button should be shown.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool
     */
    public $showCreateRelationButtonCallback;

    /**
     * Indicates the size the create relation modal should be.
     *
     * @var string
     */
    public $modalSize = '2xl';

    /**
     * Set the callback used to determine if the field is required.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool  $callback
     * @return $this
     */
    public function showCreateRelationButton(callable|bool $callback = true)
    {
        $this->showCreateRelationButtonCallback = $callback;

        return $this;
    }

    /**
     * Hide the create relation button from forms.
     *
     * @return $this
     */
    public function hideCreateRelationButton()
    {
        $this->showCreateRelationButtonCallback = false;

        return $this;
    }

    /**
     * Set the size used for the create relation modal.
     *
     * @return $this
     */
    public function modalSize(string $size)
    {
        return $this->withMeta(['modalSize' => $size]);
    }

    /**
     * Determine if Nova should show the edit pivot relation button.
     */
    public function createRelationShouldBeShown(NovaRequest $request): bool
    {
        return with($this->showCreateRelationButtonCallback, static function ($callback) use ($request) {
            if ($callback === true || (\is_callable($callback) && \call_user_func($callback, $request))) {
                return true;
            }

            return false;
        });
    }
}
