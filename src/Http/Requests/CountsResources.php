<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Query\Builder as BaseBuilder;

trait CountsResources
{
    /**
     * Build a new count query for the given query.
     */
    public function buildCountQuery(Builder $query): BaseBuilder
    {
        $baseQuery = $query->toBase();

        if (empty($baseQuery->groups)) {
            return $baseQuery;
        }

        $subQuery = $baseQuery->cloneWithout(
            $baseQuery->unions ? ['orders', 'limit', 'offset'] : ['columns', 'orders', 'limit', 'offset']
        )->cloneWithoutBindings(
            $baseQuery->unions ? ['order'] : ['select', 'order']
        )->selectRaw('1 as exists_temp');

        return $query->getConnection()
            ->query()
            ->fromSub($subQuery, 'count_temp');
    }
}
