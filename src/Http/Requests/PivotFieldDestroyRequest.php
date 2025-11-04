<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Laravel\Nova\Fields\File as FileField;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;
use Laravel\Nova\Util;

class PivotFieldDestroyRequest extends NovaRequest
{
    /**
     * Authorize that the user may attach resources of the given type.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function authorizeForAttachment(): void
    {
        if (! $this->newResourceWith($this->findModelOrFail())->authorizedToAttach(
            $this, $this->findRelatedModel()
        )) {
            abort(403);
        }
    }

    /**
     * Get the pivot model for the relationship.
     *
     * @return (\Illuminate\Database\Eloquent\Model&\Illuminate\Database\Eloquent\Relations\Concerns\AsPivot)|\Illuminate\Database\Eloquent\Relations\Pivot
     *
     * @throws \RuntimeException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function findPivotModel(): Model|Pivot
    {
        $resource = $this->findResourceOrFail();

        abort_unless($resource->hasRelatableFieldOrRelationship($this, $this->viaRelationship), 404);

        return Util::expectPivotModel(
            once(function () use ($resource) {
                return $this->findRelatedModel()->{
                    $resource->model()->{$this->viaRelationship}()->getPivotAccessor()
                };
            }),
        );
    }

    /** {@inheritDoc} */
    #[\Override]
    public function findRelatedResource(string|int|null $resourceId = null): Resource
    {
        return Nova::newResourceFromModel(
            $this->findRelatedModel($resourceId)
        );
    }

    /** {@inheritDoc} */
    #[\Override]
    public function findRelatedModel(string|int|null $resourceId = null): Model
    {
        $resource = $this->findResourceOrFail();

        abort_unless($resource->hasRelatableFieldOrRelationship($this, $this->viaRelationship), 404);

        return once(function () use ($resource, $resourceId) {
            return $resource->model()
                ->{$this->viaRelationship}()
                ->withoutGlobalScopes()
                ->lockForUpdate()
                ->findOrFail($resourceId ?? $this->relatedResourceId);
        });
    }

    /**
     * Find the field being deleted or fail if it is not found.
     *
     * @return \Laravel\Nova\Fields\Field&\Laravel\Nova\Fields\File
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function findFieldOrFail(): FileField
    {
        return $this->findRelatedResource()->resolvePivotFields($this, $this->resource)
            ->whereInstanceOf(FileField::class)
            ->findFieldByAttributeOrFail($this->field);
    }
}
