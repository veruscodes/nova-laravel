<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metable;

use function Orchestra\Sidekick\is_safe_callable;

trait MutableFields
{
    use Metable;

    /**
     * The field's resolved value.
     *
     * @var mixed
     */
    public $value;

    /**
     * The callback used to determine if the field is writable.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool|null
     */
    public $writableCallback;

    /**
     * Determine whether field is mutable.
     *
     * @var bool|null
     */
    protected $isWritable = null;

    /**
     * The callback to be used for computed field.
     *
     * @var (callable(mixed):(mixed))|null
     */
    protected $computedCallback;

    /**
     * The callback to be used for the field's default value.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(mixed))|null
     */
    protected $defaultCallback;

    /**
     * Determine whether field is readononly.
     *
     * @var bool|null
     */
    protected $isReadonly = null;

    /**
     * The callback used to determine if the field is readonly.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool|null
     */
    public $readonlyCallback;

    /**
     * Set field as immutable.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool  $callback
     * @return $this
     */
    public function immutable(callable|bool $callback = true)
    {
        $this->writableCallback = $callback;

        return $this;
    }

    /**
     * Set field as unlocked.
     *
     * @return $this
     */
    public function mutable()
    {
        return $this->immutable(false);
    }

    /**
     * Determine if the field is fillable.
     */
    public function isWritable(NovaRequest $request): bool
    {
        if (! \is_bool($this->isWritable)) {
            $this->isWritable = with($this->writableCallback, function ($callback) use ($request) {
                if ($callback === true || (\is_callable($callback) && \call_user_func($callback, $request))) {
                    $this->withMeta(['extraAttributes' => ['readonly' => true]]);

                    return true;
                }

                return false;
            });
        }

        return $this->isWritable;
    }

    /**
     * Set the value for the field.
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    /**
     * Set the field as computed.
     *
     * @param  (callable(mixed):(mixed))|null  $computedCallback
     * @return $this
     */
    public function computed(?callable $computedCallback = null)
    {
        $this->computedCallback = $computedCallback ?? fn ($resource) => data_get($resource, $this->attribute);

        return $this->onlyOnForms();
    }

    /**
     * Determine if the field is computed.
     */
    public function isComputed(): bool
    {
        /** @phpstan-ignore booleanNot.alwaysFalse, booleanAnd.alwaysFalse */
        return is_safe_callable($this->computedCallback);
    }

    /**
     * Set the callback to be used for determining the field's default value.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(mixed))|mixed  $callback
     * @return $this
     */
    public function default(mixed $callback)
    {
        $this->defaultCallback = $callback;

        return $this;
    }

    /**
     * Resolve the default value for the field.
     */
    public function resolveDefaultValue(NovaRequest $request): mixed
    {
        return $this->requestShouldResolveDefaultValue($request)
            ? $this->resolveDefaultCallback($request)
            : null;
    }

    /**
     * Resolve the default callback for the field.
     */
    public function resolveDefaultCallback(NovaRequest $request): mixed
    {
        if (\is_null($this->value) && is_safe_callable($this->defaultCallback)) {
            return \call_user_func($this->defaultCallback, $request);
        }

        return $this->defaultCallback;
    }

    /**
     * Determine if request should resolve default value.
     */
    public function requestShouldResolveDefaultValue(NovaRequest $request): bool
    {
        return $request->isCreateOrAttachRequest() || $request->isActionRequest();
    }

    /**
     * Set the callback used to determine if the field is readonly.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool  $callback
     * @return $this
     */
    public function readonly(callable|bool $callback = true)
    {
        $this->readonlyCallback = $callback;

        return $this;
    }

    /**
     * Determine if the field is readonly.
     */
    public function isReadonly(NovaRequest $request): bool
    {
        if (! \is_bool($this->isReadonly)) {
            $this->isReadonly = with($this->readonlyCallback, function ($callback) use ($request) {
                if ($callback === true || (\is_callable($callback) && \call_user_func($callback, $request))) {
                    if (\is_null($this->isWritable)) {
                        $this->immutable();
                    }

                    return true;
                }

                return false;
            });
        }

        return $this->isReadonly;
    }

    /**
     * Specify that the element should only be shown on forms.
     *
     * @return $this
     */
    abstract public function onlyOnForms();
}
