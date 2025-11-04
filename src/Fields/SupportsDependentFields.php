<?php

namespace Laravel\Nova\Fields;

trait SupportsDependentFields
{
    /**
     * List of field dependencies.
     *
     * @var array<int, \Laravel\Nova\Fields\Dependent>
     */
    protected $fieldDependencies = [];

    /**
     * Register depends on to a field.
     *
     * @param  \Laravel\Nova\Fields\Field|array<int, string|\Laravel\Nova\Fields\Field>|string  $attributes
     * @param  (callable(static, \Laravel\Nova\Http\Requests\NovaRequest, \Laravel\Nova\Fields\FormData):(void))|class-string  $mixin
     * @return $this
     */
    public function dependsOn(Field|array|string $attributes, callable|string $mixin)
    {
        $this->fieldDependencies[] = new Dependent($attributes, $mixin);

        return $this;
    }

    /**
     * Register depends on to a field on creating request.
     *
     * @param  \Laravel\Nova\Fields\Field|array<int, string|\Laravel\Nova\Fields\Field>|string  $attributes
     * @param  (callable(static, \Laravel\Nova\Http\Requests\NovaRequest, \Laravel\Nova\Fields\FormData):(void))|class-string  $mixin
     * @return $this
     */
    public function dependsOnCreating(Field|array|string $attributes, callable|string $mixin)
    {
        $this->fieldDependencies[] = new Dependent($attributes, $mixin, 'create');

        return $this;
    }

    /**
     * Register depends on to a field on updating request.
     *
     * @param  string|\Laravel\Nova\Fields\Field|array<int, string|\Laravel\Nova\Fields\Field>  $attributes
     * @param  (callable(static, \Laravel\Nova\Http\Requests\NovaRequest, \Laravel\Nova\Fields\FormData):(void))|class-string  $mixin
     * @return $this
     */
    public function dependsOnUpdating($attributes, $mixin)
    {
        $this->fieldDependencies[] = new Dependent($attributes, $mixin, 'update');

        return $this;
    }
}
