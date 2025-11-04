<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Laravel\Nova\TrashedStatus;
use Mockery as m;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * @property-read string|null $resource
 * @property-read int|string|null $resourceId
 * @property-read string|null $relatedResource
 * @property-read int|string|null $relatedResourceId
 * @property-read string|null $viaResource
 * @property-read int|string|null $viaResourceId
 * @property-read string|null $viaRelationship
 * @property-read string|null $relationshipType
 */
class NovaRequest extends FormRequest
{
    use InteractsWithRelatedResources;
    use InteractsWithResources;
    use InteractsWithResourcesSelection;

    /**
     * Creates a fake Request based on a given URI and configuration.
     *
     * @param  string|resource|null  $content
     * @param  array<string, mixed>  $routes
     */
    public static function fake(
        string $uri,
        string $method = 'GET',
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null,
        array $routes = []
    ): static {
        $request = static::create($uri, $method, $parameters, $cookies, $files, $server, $content);

        if (! empty($routes)) {
            $route = m::mock(Route::class);

            foreach ($routes as $key => $value) {
                /** @phpstan-ignore method.notFound */
                $route->shouldReceive('parameter')->with($key, null)->andReturn($value);
            }

            $request->setRouteResolver(fn () => $route);
        }

        return $request;
    }

    /** {@inheritDoc} */
    #[\Override]
    public function user($guard = null)
    {
        $guard ??= config('nova.guard');

        return parent::user($guard);
    }

    /**
     * Determine if this request is an inline create or attach request.
     */
    public function isInlineCreateRequest(): bool
    {
        return $this->isCreateOrAttachRequest() && $this->inline === 'true';
    }

    /**
     * Determine if this request is a create or attach request.
     */
    public function isCreateOrAttachRequest(): bool
    {
        return $this instanceof ResourceCreateOrAttachRequest
            || ($this->editing === 'true' && \in_array($this->editMode, ['create', 'attach']));
    }

    /**
     * Determine if this request is an update or update-attached request.
     */
    public function isUpdateOrUpdateAttachedRequest(): bool
    {
        return $this instanceof ResourceUpdateOrUpdateAttachedRequest
            || ($this->editing === 'true' && \in_array($this->editMode, ['update', 'update-attached']));
    }

    /**
     * Determine if this request is a resource index request.
     */
    public function isResourceIndexRequest(): bool
    {
        return $this instanceof ResourceIndexRequest;
    }

    /**
     * Determine if this request is a resource detail request.
     */
    public function isResourceDetailRequest(): bool
    {
        return $this instanceof ResourceDetailRequest;
    }

    /**
     * Determine if this request is a resource preview request.
     */
    public function isResourcePreviewRequest(): bool
    {
        return $this instanceof ResourcePreviewRequest;
    }

    /**
     * Determine if this request is a resource peeking request.
     */
    public function isResourcePeekingRequest(): bool
    {
        return $this instanceof ResourcePeekRequest;
    }

    /**
     * Determine if this request is a lens request.
     */
    public function isLensRequest(): bool
    {
        return $this instanceof LensRequest;
    }

    /**
     * Determine if this request is an action request.
     */
    public function isActionRequest(): bool
    {
        return $this->segment(3) == 'actions';
    }

    /**
     * Determine if this request is either create, attach, update, update-attached or action request.
     */
    public function isFormRequest(): bool
    {
        return $this->isCreateOrAttachRequest()
            || $this->isUpdateOrUpdateAttachedRequest()
            || $this->isActionRequest();
    }

    /**
     * Determine if this request is an index or detail request.
     */
    public function isPresentationRequest(): bool
    {
        return $this->isResourceIndexRequest()
            || $this->isResourceDetailRequest()
            || $this->isLensRequest();
    }

    /**
     * Get the trashed status of the request.
     */
    public function trashed(): TrashedStatus
    {
        if (\is_null($trashed = $this->trashed)) {
            return TrashedStatus::DEFAULT;
        }

        return TrashedStatus::tryFrom((string) $trashed) ?? TrashedStatus::DEFAULT;
    }

    /** {@inheritDoc} */
    #[\Override]
    public static function createFromBase(SymfonyRequest $request): static
    {
        $newRequest = parent::createFromBase($request);

        if ($request instanceof Request) {
            $newRequest->setUserResolver($request->getUserResolver());
        }

        return $newRequest;
    }
}
