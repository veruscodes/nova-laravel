<?php

namespace Laravel\Nova\Fields;

trait SupportsAutoCompletion
{
    /**
     * Enable autocomplete for the field.
     *
     * @return $this
     */
    public function withAutoCompletion(array|string|bool $value = true)
    {
        return $this->autocomplete($value);
    }

    /**
     * Disable autocomplete for the field.
     *
     * @return $this
     */
    public function withoutAutoCompletion()
    {
        return $this->autocomplete(false);
    }

    /**
     * Set a autocomplete value for the field.
     *
     * @return $this
     */
    public function autocomplete(array|string|bool $value)
    {
        $this->withMeta(['autocomplete' => match (true) {
            \is_array($value) => implode(' ', $value),
            $value === true => $this->defaultEnabledAutoCompleteValue(),
            $value === false => $this->defaultDisabledAutoCompleteValue(),
            default => $value,
        }]);

        return $this;
    }

    /**
     * Get the default disabled autocomplete value.
     */
    protected function defaultEnabledAutoCompleteValue(): string
    {
        return 'on';
    }

    /**
     * Get the default disabled autocomplete value.
     */
    protected function defaultDisabledAutoCompleteValue(): string
    {
        return 'off';
    }
}
