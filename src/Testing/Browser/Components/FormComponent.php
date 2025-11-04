<?php

namespace Laravel\Nova\Testing\Browser\Components;

use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Concerns\InteractsWithInlineCreateRelation;

class FormComponent extends Component
{
    use InteractsWithInlineCreateRelation;

    /**
     * The form unique ID.
     */
    protected ?string $formUniqueId;

    /**
     * Create a new component instance.
     */
    public function __construct(
        protected ?string $selector = null
    ) {
        //
    }

    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return $this->selector ?? '#app [dusk="content"] form:not([dusk="form-button"])';
    }

    /**
     * Set a field's value using JavaScript.
     */
    public function setFieldValue(Browser $browser, string $attribute, mixed $value): void
    {
        $browser->script("Nova.\$emit('{$this->formUniqueId}-{$attribute}-value', '{$value}')");
    }

    /**
     * Assert that the browser page contains the component.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function assert(Browser $browser): void
    {
        tap($this->selector(), function (string $selector) use ($browser) {
            $browser->pause(100)
                ->waitFor($selector)
                ->assertVisible($selector)
                ->scrollIntoView($selector);

            $this->formUniqueId = $browser->attribute($selector, 'data-form-unique-id');
        });
    }
}
