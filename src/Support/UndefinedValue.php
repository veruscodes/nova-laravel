<?php

namespace Laravel\Nova\Support;

/**
 * @internal
 */
class UndefinedValue implements \JsonSerializable
{
    /**
     * Determine if value is equivalent to "undefined" or "null".
     */
    public static function equalsTo(mixed $value): bool
    {
        return $value instanceof UndefinedValue || \is_null($value);
    }

    /**
     * Get the value as json.
     *
     * @return null
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return null;
    }
}
