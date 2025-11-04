<?php

namespace Laravel\Nova\Metrics;

use Laravel\Nova\Nova;
use Stringable;

class Table extends Metric
{
    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'table-metric';

    /**
     * The text to be displayed when the table is empty.
     *
     * @var \Stringable|string
     */
    public $emptyText = 'No Results Found...';

    /**
     * Set the text to be displayed when the table is empty.
     *
     * @return $this
     */
    public function emptyText(Stringable|string $text)
    {
        $this->emptyText = $text;

        return $this;
    }

    /**
     * Prepare the metric for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'emptyText' => Nova::__($this->emptyText),
        ]);
    }
}
