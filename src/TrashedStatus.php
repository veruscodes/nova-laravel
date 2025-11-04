<?php

namespace Laravel\Nova;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Scout\Builder as ScoutBuilder;

enum TrashedStatus: string
{
    case DEFAULT = '';

    case WITH = 'with';

    case ONLY = 'only';

    /**
     * Get trashed status from boolean.
     */
    public static function fromBoolean(bool $withTrashed): static
    {
        return $withTrashed ? self::WITH : self::DEFAULT;
    }

    /**
     * Get trashed status label.
     */
    public function name(): string
    {
        return match ($this) {
            self::DEFAULT => '-',
            self::WITH => (string) Nova::__('With Trashed'),
            self::ONLY => (string) Nova::__('Only Trashed'),
        };
    }

    /**
     * Apply the trashed state constraint to the query.
     */
    public function applySoftDeleteConstraint(Builder|ScoutBuilder $query): Builder|ScoutBuilder
    {
        return match ($this) {
            TrashedStatus::WITH => $query->withTrashed(),
            TrashedStatus::ONLY => $query->onlyTrashed(),
            default => $query,
        };
    }
}
