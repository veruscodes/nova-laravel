<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Nova;

/**
 * @method static static make(\Stringable|string|null $name = null, string $attribute = 'name')
 */
class UiAvatar extends Avatar implements Unfillable
{
    /**
     * UI-Avatars settings.
     *
     * @var array
     */
    protected $settings = [
        'size' => 300,
        'color' => '7F9CF5',
        'background' => 'EBF4FF',
    ];

    /**
     * Create a new field.
     *
     * @param  \Stringable|string|null  $name
     */
    public function __construct($name = null, string $attribute = 'name')
    {
        parent::__construct($name ?? Nova::__('Avatar'), $attribute);

        $this->exceptOnForms()
            ->disableDownload();
    }

    /**
     * Resolve the field's value.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|object  $resource
     */
    #[\Override]
    public function resolve($resource, ?string $attribute = null): void
    {
        parent::resolve($resource, $attribute);

        $callback = fn () => 'https://ui-avatars.com/api/?'.http_build_query(array_merge($this->settings, ['name' => $this->value]));

        $this->preview($callback)->thumbnail($callback);
    }

    /**
     * Set the font-size.
     *
     * @return $this
     */
    public function fontSize(float|int $fontSize)
    {
        $this->settings['font-size'] = $fontSize;

        return $this;
    }

    /**
     * Set the color.
     *
     * @return $this
     */
    public function color(string $color)
    {
        $this->settings['color'] = ltrim($color, '#');

        return $this;
    }

    /**
     * Set the background color.
     *
     * @return $this
     */
    public function backgroundColor(string $color)
    {
        $this->settings['background'] = ltrim($color, '#');

        return $this;
    }

    /**
     * Set the font weight to bold.
     *
     * @return $this
     */
    public function bold()
    {
        $this->settings['bold'] = 'true';

        return $this;
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge([
            'indexName' => '',
        ], parent::jsonSerialize());
    }
}
