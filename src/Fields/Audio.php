<?php

namespace Laravel\Nova\Fields;

use Illuminate\Support\Facades\Storage;

class Audio extends File
{
    use PresentsAudio;

    public const PRELOAD_AUTO = 'auto';

    public const PRELOAD_METADATA = 'metadata';

    public const PRELOAD_NONE = 'none';

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'audio-field';

    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     * @param  string|callable|null  $attribute
     * @param  (callable(\Illuminate\Http\Request, object, string, string, ?string, ?string):mixed)|null  $storageCallback
     */
    public function __construct($name, mixed $attribute = null, ?string $disk = 'public', ?callable $storageCallback = null)
    {
        parent::__construct($name, $attribute, $disk, $storageCallback);

        $this->acceptedTypes('audio/*')
            ->preview(fn ($value) => $value ? Storage::disk($this->getStorageDisk())->url($value) : null);
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
