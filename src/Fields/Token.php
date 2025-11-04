<?php

namespace Laravel\Nova\Fields;

use Illuminate\Support\Str;
use Laravel\Nova\Http\Requests\NovaRequest;

class Token extends Field
{
    use SupportsAutoCompletion;
    use SupportsDependentFields;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'text-field';

    /**
     * Field's mask start index.
     */
    protected int $maskStartIndex = 0;

    /**
     * Field's mask length.
     */
    protected ?int $maskLength = null;

    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     * @param  string|callable|object|null  $attribute
     * @param  (callable(mixed, mixed, ?string):(mixed))|null  $resolveCallback
     * @return void
     */
    public function __construct($name, mixed $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->withoutAutoCompletion();
    }

    /**
     * Configure field's mask.
     *
     * @return $this
     */
    public function mask(int $index, ?int $length = null)
    {
        $this->maskStartIndex = $index;
        $this->maskLength = $length;

        return $this;
    }

    /**
     * Masked the given value.
     */
    protected function maskFor(string $value): string
    {
        return Str::mask($value, '*', $this->maskStartIndex, $this->maskLength);
    }

    /**
     * Get the default disabled autocomplete value.
     */
    protected function defaultDisabledAutoCompleteValue(): string
    {
        return 'new-password';
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

        return array_merge(parent::jsonSerialize(), [
            'value' => $request->isFormRequest() ? $this->value : $this->maskFor($this->value ?? ''),
        ]);
    }
}
