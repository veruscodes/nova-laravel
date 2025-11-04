<?php

namespace Laravel\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends \Illuminate\Support\Collection<TKey, TValue>
 */
class ResourceCollection extends Collection
{
    /**
     * Return the authorized resources of the collection.
     *
     * @return static<TKey, TValue>
     */
    public function authorized(Request $request)
    {
        /** @phpstan-ignore return.type */
        return $this->filter(
            static fn ($resourceClass) => $resourceClass::authorizedToViewAny($request)
        );
    }

    /**
     * Return the resources available to be displayed in the navigation.
     *
     * @return static<TKey, TValue>
     */
    public function availableForNavigation(Request $request)
    {
        /** @phpstan-ignore return.type */
        return $this->filter(
            static fn ($resourceClass) => $resourceClass::availableForNavigation($request)
        );
    }

    /**
     * Return the searchable resources for the collection.
     *
     * @return static<TKey, TValue>
     */
    public function globallySearchable()
    {
        /** @phpstan-ignore return.type */
        return $this->filter(
            static fn ($resourceClass) => $resourceClass::$globallySearchable
        );
    }

    /**
     * Return the searchable resources for the collection.
     *
     * @return static<TKey, TValue>
     *
     * @deprecated 5.3.0 Use `ResourceCollection::globallySearchable()` instead.
     */
    #[\Deprecated('Use `ResourceCollection::globallySearchable()` instead.', since: '5.3.0')]
    public function searchable()
    {
        return $this->globallySearchable();
    }

    /**
     * Sort the resources by their group property.
     *
     * @return \Illuminate\Support\Collection<string, \Laravel\Nova\ResourceCollection<array-key, TValue>>
     */
    public function grouped()
    {
        /** @phpstan-ignore return.type */
        return $this->groupBy(
            static fn ($resourceClass, $key) => (string) $resourceClass::group()
        )->toBase()->sortKeys();
    }

    /**
     * Group the resources for display in navigation.
     *
     * @return \Illuminate\Support\Collection<string, \Laravel\Nova\ResourceCollection<array-key, TValue>>
     */
    public function groupedForNavigation(Request $request)
    {
        return $this->availableForNavigation($request)->grouped();
    }
}
