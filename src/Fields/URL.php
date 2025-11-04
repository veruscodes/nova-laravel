<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Exceptions\NovaException;
use Laravel\Nova\Http\Requests\NovaRequest;

class URL extends Text
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'url-field';

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

        $this->textAlign(Field::CENTER_ALIGN)
            ->withAutoCompletion('url');
    }

    /**
     * Allow the field to be copyable to the clipboard inside Nova.
     *
     * @return never
     *
     * @throws \Laravel\Nova\Exceptions\HelperNotSupported
     */
    public function copyable()
    {
        throw NovaException::helperNotSupported(__METHOD__, __CLASS__);
    }

    /**
     * Prepare the field element for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return with(app(NovaRequest::class), function ($request) {
            $data = parent::jsonSerialize();

            if (\is_null($data['displayedAs'])) {
                $data['displayedAs'] = match (true) {
                    $request->isResourceIndexRequest() => $this->name,
                    default => $data['value'],
                };
            }

            return $data;
        });
    }
}
