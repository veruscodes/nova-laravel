<?php

namespace Laravel\Nova\Fields;

use Illuminate\Support\Str;
use Laravel\Nova\Contracts\Previewable;
use Laravel\Nova\Http\Requests\NovaRequest;

class Slug extends Field implements Previewable
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'slug-field';

    /**
     * The field the slug should be generated from.
     *
     * @var \Laravel\Nova\Fields\Field|string|null
     */
    public $from = null;

    /**
     * The separator to use for the slug.
     *
     * @var string
     */
    public $separator = '-';

    /**
     * Whether to show the field's customize button.
     *
     * @var bool
     */
    public $showCustomizeButton = false;

    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     * @param  string|callable|object|null  $attribute
     * @param  (callable(mixed, mixed, ?string):(mixed))|null  $resolveCallback
     */
    public function __construct($name, mixed $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);
    }

    /**
     * The field the slug should be generated from.
     *
     * @return $this
     */
    public function from(Field|string $from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Set the separator used for slugifying the field.
     *
     * @return $this
     */
    public function separator(string $separator)
    {
        $this->separator = $separator;

        return $this;
    }

    /**
     * Return a preview for the given field value.
     *
     * @param  string  $value
     * @return string
     */
    public function previewFor($value)
    {
        return Str::slug($value, $this->separator);
    }

    /**
     * Prepare the element for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        $request = app(NovaRequest::class);

        $field = parent::jsonSerialize();

        $from = match (true) {
            $this->from instanceof Field => $this->from->attribute,
            ! empty($this->from) => str_replace(' ', '_', Str::lower((string) $this->from)),
            default => null,
        };

        if (! \is_null($from) && $field['readonly'] === false && $request->isUpdateOrUpdateAttachedRequest()) {
            $this->immutable();
            $this->showCustomizeButton = true;
        }

        return array_merge([
            'shouldListenToFromChanges' => ! \is_null($from) && ! $request->isUpdateOrUpdateAttachedRequest(),
            'slugFrom' => $from,
            'separator' => $this->separator,
            'showCustomizeButton' => $this->showCustomizeButton,
        ], parent::jsonSerialize());
    }
}
