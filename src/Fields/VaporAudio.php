<?php

namespace Laravel\Nova\Fields;

use Illuminate\Support\Facades\Storage;

class VaporAudio extends VaporFile
{
    use PresentsAudio;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'vapor-audio-field';

    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     * @param  string|callable|null  $attribute
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest, object, string, string, ?string, ?string):mixed)|null  $storageCallback
     */
    public function __construct($name, mixed $attribute = null, ?callable $storageCallback = null)
    {
        parent::__construct($name, $attribute, $storageCallback);

        $this->acceptedTypes('audio/*')
            ->preview(fn ($value) => $value ? Storage::disk($this->getStorageDisk())->temporaryUrl($value, now()->addMinutes(10)) : null);
    }

    /**
     * Prepare the field element for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), $this->audioAttributes());
    }
}
