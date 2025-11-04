<?php

namespace Laravel\Nova\Fields\Attachments;

use Laravel\Nova\Http\Requests\NovaRequest;

class DetachAnyAttachment
{
    /**
     * Delete any attachments from the field.
     */
    public function __invoke(NovaRequest $request): void
    {
        \call_user_func(new DetachAttachment, $request);
        \call_user_func(new DetachPendingAttachment, $request);
    }
}
