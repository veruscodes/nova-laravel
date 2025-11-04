<?php

namespace Laravel\Nova\Http\Requests;

class CreateResourceRequest extends NovaRequest
{
    /** {@inheritDoc} */
    #[\Override]
    public function isCreateOrAttachRequest(): bool
    {
        return true;
    }
}
