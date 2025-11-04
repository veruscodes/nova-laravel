<?php

namespace Laravel\Nova;

use Generator;
use Laravel\Nova\Contracts\QueryBuilder;
use Laravel\Nova\Http\Requests\NovaRequest;

use function Orchestra\Sidekick\Http\safe_int;

class GlobalSearch
{
    /**
     * Create a new global search instance.
     *
     * @param  array<int, class-string<\Laravel\Nova\Resource>>  $resources
     */
    public function __construct(
        public NovaRequest $request,
        public array $resources
    ) {
        //
    }

    /**
     * Get the matching resources.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        return iterator_to_array($this->getSearchResults(), false);
    }

    /**
     * Get the search results for the resources.
     */
    protected function getSearchResults(): Generator
    {
        foreach ($this->resources as $resourceClass) {
            $query = app()->make(QueryBuilder::class, [$resourceClass])->search(
                $this->request, $resourceClass::newModel()->newQuery()->with($resourceClass::$with),
                $this->request->search
            );

            yield from $query->limit($resourceClass::$globalSearchResults)
                ->cursor()
                ->mapInto($resourceClass)
                ->map(function ($resource) use ($resourceClass) {
                    /** @var \Laravel\Nova\Resource $resource */
                    return $this->transformResult($resourceClass, $resource);
                });
        }
    }

    /**
     * Transform the result from resource.
     *
     * @template TResourceValue of \Laravel\Nova\Resource
     *
     * @param  class-string<TResourceValue>  $resourceClass
     * @param  TResourceValue  $resource
     * @return array<string, mixed>
     */
    protected function transformResult(string $resourceClass, Resource $resource): array
    {
        $model = $resource->model();

        return [
            'resourceName' => $resourceClass::uriKey(),
            'resourceTitle' => $resourceClass::label(),
            'title' => (string) $resource->title(),
            'subTitle' => transform($resource->subtitle(), fn ($subtitle) => (string) $subtitle),
            'resourceId' => safe_int($model->getKey()),
            'url' => url(Nova::url('/resources/'.$resourceClass::uriKey().'/'.$model->getKey())),
            'avatar' => $resource->resolveAvatarUrl($this->request),
            'rounded' => $resource->resolveIfAvatarShouldBeRounded($this->request),
            'linksTo' => $resource->globalSearchLink($this->request),
        ];
    }
}
