<?php

namespace Laravel\Nova\Http\Resources;

use Laravel\Nova\Contracts\ListableField;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Fields\MorphOne;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Http\Requests\ResourceDetailRequest;
use Laravel\Nova\Resource as NovaResource;

class DetailViewResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Laravel\Nova\Http\Requests\ResourceDetailRequest  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = $this->newResourceWith($request);

        $this->authorizedResourceForRequest($request, $resource);

        $payload = with($resource->serializeForDetail($request, $resource), static function ($detail) use ($request) {
            $detail['fields'] = collect($detail['fields'])
                ->when($request->viaResource, static function ($fields) use ($request) {
                    return $fields->reject(static function ($field) use ($request) {
                        /** @var \Laravel\Nova\Fields\Field $field */
                        if ($field instanceof ListableField) {
                            return true;
                        } elseif (! $field instanceof RelatableField) {
                            return false;
                        }

                        $relatedResource = $field->resourceName == $request->viaResource;

                        return ($request->relationshipType === 'hasOne' && $field instanceof BelongsTo && $relatedResource) ||
                            ($request->relationshipType === 'morphOne' && $field instanceof MorphTo && $relatedResource) ||
                            (\in_array($request->relationshipType, ['hasOne', 'morphOne']) && ($field instanceof MorphOne || $field instanceof HasOne));
                    });
                })
                ->values()->all();

            return $detail;
        });

        /** @var \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field> $fields */
        $fields = new FieldCollection($payload['fields']);

        return [
            'title' => (string) $resource->title(),
            'panels' => $resource->availablePanelsForDetail($request, $resource, $fields),
            'resource' => $payload,
        ];
    }

    /**
     * Get current resource for the request.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function newResourceWith(ResourceDetailRequest $request): NovaResource
    {
        return $request->newResourceWith(
            tap($request->findModelQuery(), static function ($query) use ($request) {
                $resourceClass = $request->resource();
                $resourceClass::detailQuery($request, $query);
            })->firstOrFail()
        );
    }

    /**
     * Determine if resource is authorized for the request.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizedResourceForRequest(ResourceDetailRequest $request, NovaResource $resource): void
    {
        $resource->authorizeToView($request);
    }
}
