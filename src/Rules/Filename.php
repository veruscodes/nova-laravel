<?php

namespace Laravel\Nova\Rules;

use Illuminate\Contracts\Validation\Rule;

class Filename implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * https://stackoverflow.com/a/1032128
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return preg_match('/^[^\\/?*:;{}\\\\]+\\.[^\\/?*:;{}\\\\]{3}$/', $value) !== false
            && ! str_contains($value, DIRECTORY_SEPARATOR);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute field format is invalid.';
    }
}
