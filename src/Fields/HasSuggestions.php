<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Http\Requests\NovaRequest;

trait HasSuggestions
{
    /**
     * The field's suggestions callback.
     *
     * @var (callable():(iterable))|iterable|null
     */
    public $suggestions;

    /**
     * Set the callback or array to be used to determine the field's suggestions list.
     *
     * @param  (callable():(iterable))|iterable  $suggestions
     * @return $this
     */
    public function suggestions(callable|iterable $suggestions)
    {
        $this->suggestions = $suggestions;

        return $this;
    }

    /**
     * Resolve the display suggestions for the field.
     */
    public function resolveSuggestions(NovaRequest $request): ?iterable
    {
        if (\is_callable($this->suggestions)) {
            return \call_user_func($this->suggestions, $request) ?? null;
        }

        return $this->suggestions;
    }
}
