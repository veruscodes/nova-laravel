<?php

namespace Laravel\Nova\Metrics;

class PartitionColors
{
    /**
     * The pointer to the current color in the chart array.
     *
     * @var int
     */
    private $pointer = 0;

    /**
     * Create a new instance.
     *
     * @param  array<string|int, string>  $colors
     */
    public function __construct(public array $colors = [])
    {
        //
    }

    /**
     * Get the color found at the given label key.
     *
     * @return string|null
     */
    public function get(string|int $label)
    {
        return $this->colors[$label] ?? $this->next();
    }

    /**
     * Return the next color in the color list.
     */
    protected function next(): ?string
    {
        return blank($this->colors) ? null :
            tap($this->colors[
                $this->pointer % \count($this->colors)
            ] ?? null, function () {
                $this->pointer++;
            });
    }
}
