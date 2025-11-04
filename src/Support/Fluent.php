<?php

namespace Laravel\Nova\Support;

use Illuminate\Support\Arr;
use Laravel\Nova\Makeable;

class Fluent extends \Illuminate\Support\Fluent
{
    use Makeable;

    /**
     * Fill the model with an array of attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @return $this
     */
    public function fill($attributes)
    {
        foreach ($attributes as $key => $value) {
            $attribute = str_replace('->', '.', $key);

            if (! Arr::has($this->attributes, $attribute)) {
                Arr::set($this->attributes, $attribute, $value);
            }
        }

        return $this;
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @return $this
     */
    public function forceFill($attributes)
    {
        foreach ($attributes as $key => $value) {
            $attribute = str_replace('->', '.', $key);

            Arr::set($this->attributes, $attribute, $value);
        }

        return $this;
    }

    /**
     * Get an attribute from the fluent instance.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function value($key, $default = null)
    {
        if (\array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return value($default);
    }
}
