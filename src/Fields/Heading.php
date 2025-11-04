<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * @method static static make(\Stringable|string $name, string|null $attribute = null, callable|null $resolveCallback = null)
 */
class Heading extends Field implements Unfillable
{
    use AsHTML;
    use SupportsDependentFields;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'heading-field';

    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     * @param  string|callable|null  $attribute
     * @param  (callable(mixed, mixed, ?string):(mixed))|null  $resolveCallback
     */
    public function __construct($name, mixed $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->withMeta(['value' => $name]);
        $this->hideFromIndex();
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

        $displayedAs = $this->serializeDisplayedValueAsHtml($request);

        return array_merge(parent::jsonSerialize(), [
            'asHtml' => $this->asHtml,
            'displayedAs' => $displayedAs,
        ]);
    }
}
