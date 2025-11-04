<?php

namespace Laravel\Nova;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use RuntimeException;

class Util
{
    /**
     * Determine if the given request is intended for Nova.
     */
    public static function isNovaRequest(Request $request): bool
    {
        $domain = config('nova.domain');
        $path = trim(Nova::path(), '/') ?: '/';

        if (! \is_null($domain) && $domain !== config('app.url') && $path === '/') {
            if (! Str::startsWith($domain, ['http://', 'https://', '://'])) {
                $domain = $request->getScheme().'://'.$domain;
            }

            if (! \in_array($port = $request->getPort(), [443, 80]) && ! str_ends_with($domain, ":{$port}")) {
                $domain = $domain.':'.$port;
            }

            $uri = parse_url($domain);

            return isset($uri['port'])
                ? rtrim($request->getHttpHost(), '/') === $uri['host'].':'.$uri['port']
                : rtrim($request->getHttpHost(), '/') === $uri['host'];
        }

        return $request->is(...[
            $path,
            trim("{$path}/*", '/'),
            'nova-api/*',
            'nova-vendor/*',
        ]);
    }

    /**
     * Determine if given limiter can be used to throttle the request.
     */
    public static function isThrottleRequestLimiter(mixed $limiter): bool
    {
        $name = \Orchestra\Sidekick\enum_value($limiter);

        return str_contains($name, ',') || ! \is_null(RateLimiter::limiter($name));
    }

    /**
     * Convert a large integer higher than Number.MAX_SAFE_INTEGER to string.
     *
     * https://stackoverflow.com/questions/47188449/json-max-int-number/47188576
     *
     * @deprecated
     */
    public static function safeInt(mixed $value): mixed
    {
        return \Orchestra\Sidekick\Http\safe_int($value);
    }

    /**
     * Determine if the value is a callable and not a string matching an available function name.
     *
     * @deprecated
     */
    public static function isSafeCallable(mixed $value): bool
    {
        return \Orchestra\Sidekick\is_safe_callable($value);
    }

    /**
     * Determine if Fortify routes is registered for frontend routing.
     */
    public static function isFortifyRoutesRegisteredForFrontend(): bool
    {
        $appNamespace = app()->getNamespace();

        return collect([
            "{$appNamespace}Providers\FortifyServiceProvider",
            "{$appNamespace}Providers\JetstreamServiceProvider",
        ])->map(static fn ($provider) => app()->getProvider($provider))
        ->filter()
        ->isNotEmpty();
    }

    /**
     * Hydrate the value to scalar (array, string, int etc...).
     *
     * @return scalar
     *
     * @deprecated
     */
    public static function hydrate(mixed $value)
    {
        return \Orchestra\Sidekick\Eloquent\normalize_value($value);
    }

    /**
     * Resolve given value.
     */
    public static function value(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return value($value);
    }

    /**
     * Get the user guard for Laravel Nova.
     */
    public static function userGuard(): string
    {
        return config('nova.guard') ?? config('auth.defaults.guard');
    }

    /**
     * Get the user model for Laravel Nova.
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>|null
     */
    public static function userModel(): ?string
    {
        return static::userModelFromGuard(static::userGuard());
    }

    /**
     * Get the user model for Laravel Nova, use the default User model available from Framework as a fallback.
     *
     * @return class-string<\Illuminate\Foundation\Auth\User|\Illuminate\Database\Eloquent\Model>
     */
    public static function userModelOrFallback(): string
    {
        return static::userModel() ?? User::class;
    }

    /**
     * Get the user model for Laravel Nova.
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>|null
     */
    public static function userModelFromGuard(string $guard): ?string
    {
        $provider = config("auth.guards.{$guard}.provider");

        return config("auth.providers.{$provider}.model");
    }

    /**
     * Get the session authentication guard for the model.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Eloquent\Model  $model
     */
    public static function sessionAuthGuardForModel($model): ?string
    {
        if (\is_object($model)) {
            $model = $model::class;
        }

        $provider = collect(config('auth.providers'))
            ->reject(static fn ($provider) => ! ($provider['driver'] === 'eloquent' && is_a($model, $provider['model'], true)))
            ->keys()
            ->first();

        return collect(config('auth.guards'))
            ->reject(static fn ($guard) => ! ($guard['driver'] === 'session' && $guard['provider'] === $provider))
            ->keys()
            ->first();
    }

    /**
     * Resolve the model/resource for policy.
     *
     * @param  \Laravel\Nova\Resource  $resource
     * @return \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model
     */
    public static function resolveResourceOrModelForAuthorization(Resource $resource): Model|Resource
    {
        if (property_exists($resource, 'policy') && ! \is_null($resource::$policy)) {
            return $resource;
        }

        return $resource->model() ?? $resource::newModel();
    }

    /**
     * Get the dependent validation rules.
     *
     * @return array<string, string>
     *
     * @see \Illuminate\Validation\Validator::$dependentRules
     */
    public static function dependentRules(string $attribute): array
    {
        return collect([
            'After',
            'AfterOrEqual',
            'Before',
            'BeforeOrEqual',
            'Confirmed',
            'Different',
            'ExcludeIf',
            'ExcludeUnless',
            'ExcludeWith',
            'ExcludeWithout',
            'Gt',
            'Gte',
            'Lt',
            'Lte',
            'AcceptedIf',
            'DeclinedIf',
            'RequiredIf',
            'RequiredUnless',
            'RequiredWith',
            'RequiredWithAll',
            'RequiredWithout',
            'RequiredWithoutAll',
            'Prohibited',
            'ProhibitedIf',
            'ProhibitedUnless',
            'Prohibits',
            'Same',
        ])->transform(static fn ($rule) => Str::snake($rule))
        ->mapWithKeys(static fn ($rule) => ["{$rule}:" => "{$rule}:{$attribute}."])
        ->all();
    }

    /**
     * Get EOL format from content.
     */
    public static function eol(string $content): string
    {
        $lineEndingCount = [
            "\r\n" => substr_count($content, "\r\n"),
            "\r" => substr_count($content, "\r"),
            "\n" => substr_count($content, "\n"),
        ];

        return array_keys($lineEndingCount, max($lineEndingCount))[0];
    }

    /**
     * Expect the given model to implement `Pivot` class or use `AsPivot` trait.
     *
     * @param  (\Illuminate\Database\Eloquent\Model&\Illuminate\Database\Eloquent\Relations\Concerns\AsPivot)|\Illuminate\Database\Eloquent\Relations\Pivot  $pivot
     * @return (\Illuminate\Database\Eloquent\Model&\Illuminate\Database\Eloquent\Relations\Concerns\AsPivot)|\Illuminate\Database\Eloquent\Relations\Pivot
     *
     * @throws \RuntimeException
     */
    public static function expectPivotModel(Model|Pivot $pivot): Model|Pivot
    {
        throw_unless(
            \Orchestra\Sidekick\Eloquent\is_pivot_model($pivot),
            RuntimeException::class,
            \sprintf('%s model need to uses %s trait', $pivot::class, AsPivot::class),
        );

        return $pivot;
    }
}
