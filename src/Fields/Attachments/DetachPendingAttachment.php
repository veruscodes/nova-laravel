<?php

namespace Laravel\Nova\Fields\Attachments;

use Illuminate\Http\Request;

class DetachPendingAttachment
{
    /**
     * The pending attachment model.
     *
     * @var class-string<\Laravel\Nova\Fields\Attachments\PendingAttachment>
     */
    public static $model = PendingAttachment::class;

    /**
     * Delete an attachment from the field.
     */
    public function __invoke(Request $request): void
    {
        static::$model::where('draft_id', $request->draftId)
            ->when(
                $request->has('attachment'),
                static fn ($query) => $query->where('attachment', $request->attachment)
            )->get()
            ->each->purge();
    }
}
