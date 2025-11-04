<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Http\Requests\NovaRequest;

use function Orchestra\Sidekick\is_safe_callable;

trait Searchable
{
    use SupportsAutoCompletion;

    /**
     * Indicates if this relationship is searchable.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool
     */
    public $searchable = false;

    /**
     * Indicates if the subtitle will be shown within search results.
     *
     * @var bool
     */
    public $withSubtitles = false;

    /**
     * The debounce amount to use when searching this field.
     *
     * @var int
     */
    public $debounce = 500;

    /**
     * Specify if the relationship should be searchable.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool  $searchable
     * @return $this
     */
    public function searchable(callable|bool $searchable = true)
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Enable subtitles within the related search results.
     *
     * @return $this
     */
    public function withSubtitles()
    {
        $this->withSubtitles = true;

        return $this;
    }

    /**
     * Set the debounce period for use in searchable select inputs.
     *
     * @return $this
     */
    public function debounce(int $amount)
    {
        $this->debounce = $amount;

        return $this;
    }

    /**
     * Determine if current field are searchable.
     */
    public function isSearchable(NovaRequest $request): bool
    {
        if (is_safe_callable($this->searchable)) {
            $this->searchable = \call_user_func($this->searchable, $request);
        }

        return $this->searchable;
    }
}
