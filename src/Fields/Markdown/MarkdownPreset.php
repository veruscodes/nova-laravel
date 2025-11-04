<?php

namespace Laravel\Nova\Fields\Markdown;

interface MarkdownPreset
{
    /**
     * Convert the given content from markdown to HTML.
     *
     * @return string
     */
    public function convert(string $content);
}
