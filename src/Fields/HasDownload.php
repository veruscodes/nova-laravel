<?php

namespace Laravel\Nova\Fields;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @phpstan-type TResourceModel \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent|\stdClass
 * @phpstan-type TDownloadResponse \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
 * @phpstan-type TDownloadResponseCallback (callable(\Laravel\Nova\Http\Requests\NovaRequest, TResourceModel, ?string, ?string):(TDownloadResponse))
 */
trait HasDownload
{
    /**
     * The callback used to generate the download HTTP response.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest, object, ?string, ?string):(mixed))|null
     *
     * @phpstan-var TDownloadResponseCallback|null
     */
    public $downloadResponseCallback;

    /**
     * Determine if the file is able to be downloaded.
     *
     * @var bool
     */
    public $downloadsAreEnabled = true;

    /**
     * Disable downloading the file.
     *
     * @return $this
     */
    public function disableDownload()
    {
        $this->downloadsAreEnabled = false;

        return $this;
    }

    /**
     * Specify the callback that should be used to create a download HTTP response.
     *
     * @param  callable(\Laravel\Nova\Http\Requests\NovaRequest, object, ?string, ?string):mixed  $downloadResponseCallback
     *
     * @phpstan-param TDownloadResponseCallback $downloadResponseCallback
     *
     * @return $this
     */
    public function download(callable $downloadResponseCallback)
    {
        $this->downloadResponseCallback = $downloadResponseCallback;

        return $this;
    }

    /**
     * Create an HTTP response to download the underlying field.
     */
    public function toDownloadResponse(NovaRequest $request, Resource $resource): Response|RedirectResponse|BinaryFileResponse|StreamedResponse
    {
        return \call_user_func(
            $this->downloadResponseCallback,
            $request,
            $resource->resource,
            $this->getStorageDisk(),
            $this->getStoragePath()
        );
    }
}
