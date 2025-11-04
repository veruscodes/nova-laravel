<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Exceptions\HelperNotSupported;
use Laravel\Nova\Http\Requests\NovaRequest;

trait AsHTML
{
    /**
     * Indicates if the field value should be displayed as HTML.
     *
     * @var bool
     */
    public $asHtml = false;

    /**
     * Indicates if the field value should be displayed as encoded HTML.
     *
     * @var bool
     */
    public $asEncodedHtml = false;

    /**
     * Display the field as raw HTML using Vue.
     *
     * @return $this
     *
     * @throws \Laravel\Nova\Exceptions\HelperNotSupported
     */
    public function asHtml()
    {
        if (property_exists($this, 'copyable') && $this->copyable === true) {
            throw new HelperNotSupported("The `asHtml` option is not available on fields set to `copyable`. Please remove the `copyable` method from the {$this->name} field to enable `asHtml`.");
        }

        $this->asHtml = true;

        return $this;
    }

    /**
     * Display the field as encoded HTML.
     *
     * @return $this
     *
     * @throws \Laravel\Nova\Exceptions\HelperNotSupported
     */
    public function asEncodedHtml()
    {
        $this->asHtml();

        $this->asEncodedHtml = true;

        return $this;
    }

    /**
     * Serialize field's display value.
     */
    public function serializeDisplayedValueAsHtml(NovaRequest $request): ?string
    {
        if ($this->asEncodedHtml === true && ! $request->isFormRequest()) {
            $value = $this->displayedAs ?? $this->value;

            $this->usesCustomizedDisplay = ! \is_null($value);

            return $this->usesCustomizedDisplay === true ? e($value) : $value;
        }

        return $this->displayedAs;
    }
}
