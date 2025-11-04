<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Laravel\Nova\Http\Requests\NovaRequest;

class FieldAttachmentController extends Controller
{
    /**
     * Store an attachment for a Trix field.
     */
    public function store(NovaRequest $request): JsonResponse
    {
        /** @var \Laravel\Nova\Fields\Field&\Laravel\Nova\Contracts\Storable $field */
        $field = $request->newResource()
            ->availableFields($request)
            ->filter(static fn ($field) => optional($field)->withFiles === true)
            ->findFieldByAttributeOrFail($request->field);

        /** @phpstan-ignore property.notFound */
        $payload = \call_user_func($field->attachCallback, $request);

        return response()->json($payload);
    }

    /**
     * Delete a single, persisted attachment for a Trix field by URL.
     */
    public function destroyAttachment(NovaRequest $request): Response
    {
        /** @var \Laravel\Nova\Fields\Field&\Laravel\Nova\Contracts\Storable $field */
        $field = $request->newResource()
            ->availableFields($request)
            ->filter(static fn ($field) => optional($field)->withFiles === true)
            ->findFieldByAttributeOrFail($request->field);

        /** @phpstan-ignore property.notFound */
        \call_user_func($field->detachCallback, $request);

        return response()->noContent(200);
    }

    /**
     * Purge all pending attachments for a Trix field.
     */
    public function destroyPending(NovaRequest $request): Response
    {
        /** @var \Laravel\Nova\Fields\Field&\Laravel\Nova\Contracts\Storable $field */
        $field = $request->newResource()
            ->availableFields($request)
            ->filter(static fn ($field) => optional($field)->withFiles === true)
            ->findFieldByAttributeOrFail($request->field);

        /** @phpstan-ignore property.notFound */
        \call_user_func($field->discardCallback, $request);

        return response()->noContent(200);
    }

    /**
     * Return a new draft ID for the field.
     */
    public function draftId(): JsonResponse
    {
        return response()->json([
            'draftId' => Str::uuid(),
        ]);
    }
}
