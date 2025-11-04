<?php

namespace Laravel\Nova\Http\Resources;

use Laravel\Nova\Http\Requests\ResourceCreateOrAttachRequest;
use Laravel\Nova\Resource as NovaResource;

class ReplicateViewResource extends CreateViewResource
{
    /**
     * Construct a new Create View Resource.
     */
    public function __construct(protected string|int|null $fromResourceId = null)
    {
        //
    }

    /** {@inheritDoc} */
    #[\Override]
    public function newResourceWith(ResourceCreateOrAttachRequest $request): NovaResource
    {
        $query = $request->findModelQuery($this->fromResourceId);

        $resourceClass = $request->resource();

        $resourceClass::replicateQuery($request, $query);

        return tap($request->newResourceWith($query->firstOrFail()), static function (NovaResource $resource) use ($request) {
            $resource->authorizeToReplicate($request);
        })->replicate();
    }
}
