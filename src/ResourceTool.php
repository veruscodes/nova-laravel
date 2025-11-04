<?php

namespace Laravel\Nova;

use Illuminate\Support\Str;

/**
 * @method static static make()
 */
#[\AllowDynamicProperties]
class ResourceTool extends Panel
{
    use Makeable;
    use ProxiesCanSeeToGate;

    /**
     * The resource tool element.
     *
     * @var \Laravel\Nova\Element
     */
    public $element;

    /**
     * The resource tool's component.
     *
     * @var string|null
     */
    public $toolComponent;

    /**
     * Create a new resource tool instance.
     */
    public function __construct()
    {
        parent::__construct($this->name(), [new ResourceToolElement($this->toolComponent())]);

        $this->element = $this->data[0];
    }

    /**
     * Get the displayable name of the resource tool.
     *
     * @return \Stringable|string
     */
    public function name()
    {
        return $this->name ?: Nova::humanize($this::class);
    }

    /**
     * Get the component name for the resource tool.
     *
     * @return string
     */
    public function toolComponent()
    {
        return $this->toolComponent ?? Str::kebab(class_basename($this::class));
    }

    /**
     * Set the callback to be run to authorize viewing the card.
     *
     * @param  callable(\Illuminate\Http\Request):bool  $callback
     * @return $this
     */
    public function canSee(callable $callback)
    {
        $this->element->canSee($callback);

        return $this;
    }

    /** {@inheritDoc} */
    #[\Override]
    public function withMeta(array $meta)
    {
        parent::withMeta($meta);
        $this->element->withMeta($meta);

        return $this;
    }

    /**
     * Dynamically proxy method calls to meta information.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return $this
     */
    public function __call($method, $parameters)
    {
        return $this->withMeta([$method => ($parameters[0] ?? true)]);
    }
}
