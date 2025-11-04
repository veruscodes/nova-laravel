<?php

namespace Laravel\Nova\Fields;

use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Support\FluentDecorator;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends \Laravel\Nova\Support\FluentDecorator<TKey, TValue>
 */
class FormData extends FluentDecorator
{
    /**
     * Create a new fluent instance.
     *
     * @param  iterable<TKey, TValue>  $attributes
     */
    public function __construct($attributes, protected NovaRequest $request)
    {
        parent::__construct($attributes);
    }

    /**
     * Make fluent payload from request.
     *
     * @param  array<string, mixed>  $fields
     * @return static
     */
    public static function make(NovaRequest $request, array $fields)
    {
        if (! \is_null($request->resource) && ! \is_null($request->resourceId)) {
            $fields["resource:{$request->resource}"] = $request->resourceId;
        }

        if (! \is_null($request->viaResource) && ! \is_null($request->viaResourceId)) {
            $fields["resource:{$request->viaResource}"] = $request->viaResourceId;
        }

        if (! \is_null($request->relatedResource) && ! \is_null($request->relatedResourceId)) {
            $fields["resource:{$request->relatedResource}"] = $request->relatedResourceId;
        }

        return new static($fields, $request);
    }

    /**
     * Make fluent payload from request only on specific keys.
     *
     * @param  array<int, string>  $onlyAttributes
     * @return static
     */
    public static function onlyFrom(NovaRequest $request, array $onlyAttributes)
    {
        $fields = $request->method() === 'GET' && ! \is_null($dependsOn = $request->query('dependsOn'))
            ? Arr::only(json_decode(base64_decode($dependsOn), true), $onlyAttributes)
            : $request->only($onlyAttributes);

        return static::make($request, $fields);
    }

    /**
     * Get a resource attribute from the fluent instance.
     */
    public function resource(string $uriKey, mixed $default = null): mixed
    {
        $key = "resource:{$uriKey}";

        if (! empty($this->request->viaRelationship)
            && ($uriKey === $this->request->relatedResource || $uriKey === $this->request->viaResource)
        ) {
            return $this->fluent->get($key, $this->fluent->get($this->request->viaRelationship, $default));
        }

        return $this->fluent->get($key, $default);
    }

    /**
     * Retrieve input from the request as a Stringable instance.
     */
    public function str(string $key, mixed $default = ''): Stringable
    {
        return $this->string($key, $default);
    }

    /**
     * Retrieve input from the request as a Stringable instance.
     */
    public function string(string $key, mixed $default = ''): Stringable
    {
        return Str::of($this->fluent->get($key, $default));
    }

    /**
     * Retrieve input from the request as a json value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function json($key, $default = null)
    {
        $value = $this->fluent->get($key, $default);

        return \is_string($value) ? json_decode($value, true) : $value;
    }

    /**
     * Retrieve input as a boolean value.
     *
     * Returns true when value is "1", "true", "on", and "yes". Otherwise, returns false.
     */
    public function boolean(string $key, bool $default = false): bool
    {
        return filter_var($this->fluent->get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Retrieve input as an integer value.
     */
    public function integer(string $key, int $default = 0): int
    {
        return \intval($this->fluent->get($key, $default));
    }

    /**
     * Retrieve input as a float value.
     */
    public function float(string $key, float $default = 0.0): float
    {
        return \floatval($this->fluent->get($key, $default));
    }

    /**
     * Retrieve input from the request as a Carbon instance.
     *
     * @throws \Carbon\Exceptions\InvalidFormatException
     */
    public function date(string $key, ?string $format = null, ?string $tz = null): ?CarbonInterface
    {
        $value = $this->fluent->get($key);

        if (! filled($value)) {
            return null;
        }

        if (\is_null($format)) {
            return Date::parse($value, $tz);
        }

        return Date::createFromFormat($format, $value, $tz);
    }
}
