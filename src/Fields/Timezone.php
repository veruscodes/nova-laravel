<?php

namespace Laravel\Nova\Fields;

use DateTimeZone;

/**
 * @phpstan-type TOptionValue string
 * @phpstan-type TOptionLabel string
 */
class Timezone extends Select
{
    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     * @param  string|callable|null  $attribute
     */
    public function __construct($name, mixed $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->options(
            collect(DateTimeZone::listIdentifiers(DateTimeZone::ALL))
                ->mapWithKeys(static fn ($timezone) => [$timezone => $timezone])
                ->all()
        );
    }
}
