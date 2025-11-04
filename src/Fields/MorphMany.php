<?php

namespace Laravel\Nova\Fields;

class MorphMany extends HasMany
{
    /**
     * Get the relationship type.
     */
    #[\Override]
    public function relationshipType(): string
    {
        return 'morphMany';
    }
}
