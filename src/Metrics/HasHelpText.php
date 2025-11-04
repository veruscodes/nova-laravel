<?php

namespace Laravel\Nova\Metrics;

trait HasHelpText
{
    /**
     * The help text for the metric.
     *
     * @var \Stringable|string|null
     */
    public $helpText = null;

    /**
     * The width of the help text tooltip.
     *
     * @var string|int
     */
    public $helpWidth = 250;

    /**
     * Add help text to the metric.
     *
     * @param  \Stringable|string|null  $text
     * @return $this
     */
    public function help($text)
    {
        $this->helpText = $text;

        return $this;
    }

    /**
     * Return the help text for the metric.
     *
     * @return \Stringable|string
     */
    public function getHelpText()
    {
        return $this->helpText;
    }

    /**
     * Set the width for the help text tooltip.
     *
     * @return $this
     */
    public function helpWidth(string|int $helpWidth)
    {
        $this->helpWidth = $helpWidth;

        return $this;
    }

    /**
     * Return the width of the help text tooltip.
     *
     * @return string|int
     */
    public function getHelpWidth()
    {
        return $this->helpWidth;
    }
}
