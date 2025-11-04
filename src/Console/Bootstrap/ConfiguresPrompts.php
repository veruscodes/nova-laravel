<?php

namespace Laravel\Nova\Console\Bootstrap;

use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Foundation\Application;
use Laravel\Prompts\Prompt;
use stdClass;

class ConfiguresPrompts
{
    /**
     * Bootstrap the given application.
     */
    public function bootstrap(ApplicationContract $app): void
    {
        /** @phpstan-ignore if.alwaysTrue */
        if (version_compare(Application::VERSION, '11.0.0', '>=')) {
            return;
        }

        /** @phpstan-ignore deadCode.unreachable */
        Prompt::validateUsing(fn (Prompt $prompt) => $this->validatePrompt($app, $prompt->value(), $prompt->validate));
    }

    /**
     * Validate the given prompt value using the validator.
     *
     * @param  mixed  $value
     * @param  mixed  $rules
     * @return ?string
     */
    protected function validatePrompt(ApplicationContract $app, $value, $rules)
    {
        if ($rules instanceof stdClass) {
            $messages = $rules->messages ?? [];
            $attributes = $rules->attributes ?? [];
            $rules = $rules->rules ?? null;
        }

        if (! $rules) {
            return null;
        }

        $field = 'answer';

        if (\is_array($rules) && ! array_is_list($rules)) {
            [$field, $rules] = [key($rules), current($rules)];
        }

        return $this->getPromptValidatorInstance(
            $app, $field, $value, $rules, $messages ?? [], $attributes ?? []
        )->errors()->first();
    }

    /**
     * Get the validator instance that should be used to validate prompts.
     *
     * @param  mixed  $field
     * @param  mixed  $value
     * @param  mixed  $rules
     * @return \Illuminate\Validation\Validator
     */
    protected function getPromptValidatorInstance(
        ApplicationContract $app,
        $field,
        $value,
        $rules,
        array $messages = [],
        array $attributes = []
    ) {
        return $app['validator']->make(
            [$field => $value],
            [$field => $rules],
            empty($messages) ? [] : $messages,
            empty($attributes) ? [] : $attributes,
        );
    }
}
