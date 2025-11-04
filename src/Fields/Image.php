<?php

namespace Laravel\Nova\Fields;

use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Contracts\Cover;

class Image extends File implements Cover
{
    use PresentsImages;

    public const ASPECT_AUTO = 'aspect-auto';

    public const ASPECT_SQUARE = 'aspect-square';

    /**
     * Indicates if the element should be shown on the index view.
     *
     * @var bool
     */
    public $showOnIndex = true;

    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     * @param  string|callable|null  $attribute
     * @param  (callable(\Illuminate\Http\Request, object, string, string, ?string, ?string):(mixed))|null  $storageCallback
     */
    public function __construct($name, mixed $attribute = null, ?string $disk = null, ?callable $storageCallback = null)
    {
        parent::__construct($name, $attribute, $disk, $storageCallback);

        $this->acceptedTypes('image/*');

        $this->thumbnail(function () {
            return $this->value ? Storage::disk($this->getStorageDisk())->url($this->value) : null;
        })->preview(function () {
            return $this->value ? Storage::disk($this->getStorageDisk())->url($this->value) : null;
        });
    }

    /**
     * Prepare the field element for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), $this->imageAttributes());
    }
}
