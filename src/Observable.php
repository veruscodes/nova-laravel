<?php

namespace Laravel\Nova;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * @method static static make(\Illuminate\Database\Eloquent\Model|string $eloquent, array|string $classes)
 */
class Observable
{
    use Makeable;

    /**
     * Construct a new observable for an Eloquent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $eloquent
     * @param  array<int, object|class-string>|object|class-string  $classes
     */
    public function __construct(Model|string $eloquent, array|object|string $classes)
    {
        $model = \is_string($eloquent) ? new $eloquent : $eloquent;

        $dispatcher = $model->getEventDispatcher();

        foreach (Arr::wrap($classes) as $class) {
            $this->registerObserver($model, $dispatcher, $class);
        }
    }

    /**
     * Register a single observer with the model.
     *
     * @param  object|class-string  $observer
     *
     * @throws \RuntimeException
     */
    protected function registerObserver(Model $model, Dispatcher $eventDispatcher, object|string $observer): void
    {
        $observerName = $this->resolveObserverClassName($observer);

        $name = $model::class;

        foreach ($model->getObservableEvents() as $event) {
            if (method_exists($observer, $event)) {
                $eventDispatcher->listen("eloquent.{$event}: {$name}", $this->createCallbackForListenerOnServingNova($observerName, $event));
            }
        }
    }

    /**
     * Create a callable for dispatching a listener on Nova request.
     *
     * @return \Closure():mixed
     */
    protected function createCallbackForListenerOnServingNova(mixed $listener, string $method): Closure
    {
        return static function () use ($method, $listener) {
            $payload = \func_get_args();

            return Nova::whenServing(static fn () => app()->make($listener)->$method(...$payload));
        };
    }

    /**
     * Resolve the observer's class name from an object or string.
     *
     * @param  object|class-string  $class
     * @return class-string
     *
     * @throws \InvalidArgumentException
     */
    protected function resolveObserverClassName(object|string $class): string
    {
        if (\is_object($class)) {
            return $class::class;
        }

        if (class_exists($class)) {
            return $class;
        }

        throw new InvalidArgumentException('Unable to find observer: '.$class);
    }
}
