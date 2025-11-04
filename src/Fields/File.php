<?php

namespace Laravel\Nova\Fields;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Contracts\Deletable as DeletableContract;
use Laravel\Nova\Contracts\Downloadable as DownloadableContract;
use Laravel\Nova\Contracts\Storable as StorableContract;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * @method static static make(\Stringable|string $name, string|callable|null $attribute = null, string|null $disk = null, callable|null $storageCallback = null)
 */
class File extends Field implements DeletableContract, DownloadableContract, StorableContract
{
    use AcceptsTypes;
    use Deletable;
    use HasDownload;
    use HasPreview;
    use HasThumbnail;
    use Storable;
    use SupportsDependentFields;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'file-field';

    /**
     * The text alignment for the field's text in tables.
     *
     * @var string
     */
    public $textAlign = 'center';

    /**
     * Indicates if the element should be shown on the index view.
     *
     * @var bool
     */
    public $showOnIndex = false;

    /**
     * The callback that should be executed to store the file.
     *
     * @var callable(\Illuminate\Http\Request, \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent, string, string, ?string, ?string):mixed
     */
    public $storageCallback;

    /**
     * The callback that should be used to determine the file's storage name.
     *
     * @var (callable(\Illuminate\Http\Request, \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent, string, string, ?string, ?string):string)|null
     * */
    public $storeAsCallback;

    /**
     * The column where the file's original name should be stored.
     *
     * @var string|null
     */
    public $originalNameColumn = null;

    /**
     * The column where the file's size should be stored.
     *
     * @var string|null
     */
    public $sizeColumn = null;

    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     * @param  string|callable|null  $attribute
     * @param  (callable(\Illuminate\Http\Request, \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent, string, string, ?string, ?string):(mixed))|null  $storageCallback
     */
    public function __construct($name, mixed $attribute = null, ?string $disk = null, ?callable $storageCallback = null)
    {
        parent::__construct($name, $attribute);

        $this->disk($disk);

        $this
            ->store(
                $storageCallback ?? function ($request, $model, $attribute, $requestAttribute) {
                    return $this->mergeExtraStorageColumns($request, $requestAttribute, [
                        $this->attribute => $this->storeFile($request, $model, $attribute, $requestAttribute),
                    ]);
                }
            )
            ->thumbnail(static fn () => null)
            ->preview(static fn () => null)
            ->download(function ($request, $model) {
                $name = $this->originalNameColumn ? $model->{$this->originalNameColumn} : null;

                return Storage::disk($this->getStorageDisk())->download($this->value, $name);
            })->delete(function () {
                if ($this->value) {
                    Storage::disk($this->getStorageDisk())->delete($this->value);

                    return $this->columnsThatShouldBeDeleted();
                }
            });
    }

    /**
     * Store the file on disk.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     */
    protected function storeFile(Request $request, $model, string $attribute, string $requestAttribute): string
    {
        $file = $this->retrieveFileFromRequest($request, $requestAttribute);

        if (! $this->storeAsCallback) {
            return $file->store($this->getStorageDir(), $this->getStorageDisk());
        }

        return $file->storeAs(
            $this->getStorageDir(),
            \call_user_func($this->storeAsCallback, $request, $model, $attribute, $requestAttribute, $this->getStorageDir(), $this->getStorageDisk()),
            $this->getStorageDisk()
        );
    }

    /**
     * Merge the specified extra file information columns into the storable attributes.
     */
    protected function mergeExtraStorageColumns(Request $request, string $requestAttribute, array $attributes): array
    {
        $file = $this->retrieveFileFromRequest($request, $requestAttribute);

        if ($this->originalNameColumn) {
            $attributes[$this->originalNameColumn] = $file->getClientOriginalName();
        }

        if ($this->sizeColumn) {
            $attributes[$this->sizeColumn] = $file->getSize();
        }

        return $attributes;
    }

    /**
     * Get an array of the columns that should be deleted and their values.
     */
    protected function columnsThatShouldBeDeleted(): array
    {
        $attributes = [$this->attribute => null];

        if ($this->originalNameColumn) {
            $attributes[$this->originalNameColumn] = null;
        }

        if ($this->sizeColumn) {
            $attributes[$this->sizeColumn] = null;
        }

        return $attributes;
    }

    /**
     * Get the disk that the field is stored on.
     *
     * @return string|null
     */
    public function getStorageDisk()
    {
        return $this->disk ?: $this->getDefaultStorageDisk();
    }

    /**
     * Specify the callback that should be used to store the file.
     *
     * @param  callable(\Illuminate\Http\Request, \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent, string, string, ?string, ?string):mixed  $storageCallback
     * @return $this
     */
    public function store(callable $storageCallback)
    {
        $this->storageCallback = $storageCallback;

        return $this;
    }

    /**
     * Specify the callback that should be used to determine the file's storage name.
     *
     * @param  callable(\Illuminate\Http\Request, \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent, string, string, ?string, ?string):string  $storeAsCallback
     * @return $this
     */
    public function storeAs(callable $storeAsCallback)
    {
        $this->storeAsCallback = $storeAsCallback;

        return $this;
    }

    /**
     * Specify the callback that should be used to retrieve the thumbnail URL.
     *
     * @param  callable(mixed, string, mixed):?string  $thumbnailUrlCallback
     * @return $this
     */
    public function thumbnail(callable $thumbnailUrlCallback)
    {
        $this->thumbnailUrlCallback = $thumbnailUrlCallback;

        return $this;
    }

    /**
     * Specify the callback that should be used to retrieve the preview URL.
     *
     * @param  callable(mixed, ?string, mixed):?string  $previewUrlCallback
     * @return $this
     */
    public function preview(callable $previewUrlCallback)
    {
        $this->previewUrlCallback = $previewUrlCallback;

        return $this;
    }

    /**
     * Specify the column where the file's original name should be stored.
     *
     * @return $this
     */
    public function storeOriginalName(string $column)
    {
        $this->originalNameColumn = $column;

        return $this;
    }

    /**
     * Specify the column where the file size should be stored.
     *
     * @return $this
     */
    public function storeSize(string $column)
    {
        $this->sizeColumn = $column;

        return $this;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     */
    public function fillForAction(NovaRequest $request, object $model): void
    {
        if (isset($request[$this->attribute])) {
            $model->{$this->attribute} = $request[$this->attribute];
        }
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     */
    protected function fillAttribute(NovaRequest $request, string $requestAttribute, object $model, string $attribute): mixed
    {
        if (\is_null($file = $this->retrieveFileFromRequest($request, $requestAttribute))) {
            return null;
        }

        $hasExistingFile = ! \is_null($this->getStoragePath());

        $result = \call_user_func(
            $this->storageCallback,
            $request,
            $model,
            $attribute,
            $requestAttribute,
            $this->getStorageDisk(),
            $this->getStorageDir()
        );

        if ($result === true) {
            return null;
        }

        if ($result instanceof Closure) {
            return $result;
        }

        if (! \is_array($result)) {
            return $model->{$attribute} = $result;
        }

        foreach ($result as $key => $value) {
            $model->{$key} = $value;
        }

        if ($this->isPrunable() && $hasExistingFile) {
            return function () use ($model, $request) {
                \call_user_func(
                    $this->deleteCallback,
                    $request,
                    $model,
                    $this->getStorageDisk(),
                    $this->getStoragePath()
                );
            };
        }

        return null;
    }

    /**
     * Get the full path that the field is stored at on disk.
     *
     * @return string|null
     */
    public function getStoragePath()
    {
        return $this->value;
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'thumbnailUrl' => $this->resolveThumbnailUrl(),
            'previewUrl' => $this->resolvePreviewUrl(),
            'downloadable' => $this->downloadsAreEnabled && isset($this->downloadResponseCallback) && ! empty($this->value),
            'deletable' => isset($this->deleteCallback) && $this->deletable,
            'acceptedTypes' => $this->acceptedTypes,
        ]);
    }

    /**
     * Retrieve file instance from request.
     */
    protected function retrieveFileFromRequest(Request $request, string $requestAttribute): ?UploadedFile
    {
        $file = str_contains($requestAttribute, '.') && $request->filled($requestAttribute)
            ? data_get($request->all(), $requestAttribute)
            : $request->file($requestAttribute);

        return ! \is_null($file) && $file->isValid() ? $file : null;
    }
}
