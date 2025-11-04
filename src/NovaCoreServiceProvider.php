<?php

namespace Laravel\Nova;

use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Logout;
use Illuminate\Container\Container;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Middleware\CheckResponseForModifications;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Nova\Auth\Adapters\SessionImpersonator;
use Laravel\Nova\Contracts\ImpersonatesUsers;
use Laravel\Nova\Contracts\QueryBuilder;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Http\Middleware\Authenticate;
use Laravel\Nova\Http\Middleware\BootTools;
use Laravel\Nova\Http\Middleware\DispatchServingNovaEvent;
use Laravel\Nova\Http\Middleware\RedirectIfAuthenticated;
use Laravel\Nova\Http\Middleware\ServeNova;
use Laravel\Nova\Http\MiddlewareCollection;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Listeners\BootNova;
use Laravel\Nova\Query\Builder;
use Laravel\Octane\Events\RequestReceived;
use Spatie\Once\Cache;

/**
 * The primary purpose of this service provider is to push the ServeNova
 * middleware onto the middleware stack so we only need to register a
 * minimum number of resources for all other incoming app requests.
 */
class NovaCoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        Nova::booted(BootNova::class);

        if ($this->app->runningInConsole()) {
            $this->app->register(NovaServiceProvider::class);
        }

        if (! $this->app->configurationIsCached()) {
            $this->mergeConfigFrom(__DIR__.'/../config/nova.php', 'nova');
        }

        Route::aliasMiddleware('nova.guest', RedirectIfAuthenticated::class);
        Route::aliasMiddleware('nova.auth', Authenticate::class);
        Route::middlewareGroup('nova:serving', [
            DispatchServingNovaEvent::class,
            BootTools::class,
        ]);
        (new MiddlewareCollection($middlewares = config('nova.middleware', [])))->asMiddlewareGroup('nova');
        (new MiddlewareCollection(config('nova.api_middleware', [])))->asMiddlewareGroup('nova:api');
        (new MiddlewareCollection(config('nova.asset_middleware', [
            'nova:api',
            CheckResponseForModifications::class,
        ])))->asMiddlewareGroup('nova:asset');
        (new MiddlewareCollection($middlewares))->appendsRedirectIfAuthenticatedMiddleware()->asMiddlewareGroup('nova:auth');

        $this->app->make(HttpKernel::class)
            ->pushMiddleware(ServeNova::class);

        $this->app->afterResolving(NovaRequest::class, static function ($request, $app) {
            if (! $app->bound(NovaRequest::class)) {
                $app->instance(NovaRequest::class, $request);
            }
        });

        $this->registerEvents();
        $this->registerResources();
        $this->registerJsonVariables();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (! \defined('NOVA_PATH')) {
            \define('NOVA_PATH', realpath(__DIR__.'/../'));
        }

        $this->app->singleton(ImpersonatesUsers::class, SessionImpersonator::class);

        $this->app->bind(QueryBuilder::class, static fn ($app, $parameters) => new Builder(...$parameters));

        $this->registerAboutCommand();
    }

    /**
     * Register the package about command.
     */
    protected function registerAboutCommand(): void
    {
        AboutCommand::add('Nova', static function () {
            $formatEnabledStatus = static function ($value) {
                return $value ? '<fg=yellow;options=bold>ENABLED</>' : 'OFF';
            };

            return [
                'Version' => fn () => Nova::version(),
                'Name' => fn () => config('nova.name'),
                'URL' => fn () => Str::of((config('nova.domain') ?? config('app.url')).Nova::path())->replace(['http://', 'https://'], ''),

                'Theme Switcher' => AboutCommand::format(Nova::$withThemeSwitcher, console: $formatEnabledStatus),
                'RTL Enabled' => AboutCommand::format(Nova::rtlEnabled(), console: $formatEnabledStatus),
                'Pagination' => static fn () => config('nova.pagination'),
                'Storage Disk' => static fn () => config('nova.storage_disk'),
                'Currency' => static fn () => config('nova.currency'),

                'Notification Center' => AboutCommand::format(Nova::$withNotificationCenter, console: $formatEnabledStatus),
                'Notification Polling' => AboutCommand::format(Nova::$notificationPollingInterval, console: static fn ($value) => "{$value}s"),

                'Authentication' => AboutCommand::format(Nova::routes()->withAuthentication, console: $formatEnabledStatus),
                'Authentication Guard' => AboutCommand::format(Util::userGuard(), console: static fn ($value) => $value ?? 'null'),
                'Authentication User' => static fn () => Util::userModel(),

                'Password Reset' => AboutCommand::format(Nova::routes()->withPasswordReset, console: $formatEnabledStatus),
                'Password Reset Broker' => AboutCommand::format(config('nova.passwords'), console: static fn ($value) => $value ?? 'null'),

                'Global Search' => AboutCommand::format(Nova::$withGlobalSearch, console: $formatEnabledStatus),
                'Global Debounce' => AboutCOmmand::format(Nova::$debounce, console: static fn ($value) => "{$value}s"),
            ];
        });
    }

    /**
     * Register the package events.
     */
    protected function registerEvents(): void
    {
        tap($this->app['events'], static function ($event) {
            $event->listen(Attempting::class, static function () {
                app(ImpersonatesUsers::class)->flushImpersonationData(request());
            });

            $event->listen(Logout::class, static function () {
                app(ImpersonatesUsers::class)->flushImpersonationData(request());
            });

            /** @phpstan-ignore class.notFound */
            $event->listen(RequestReceived::class, static function ($event) {
                Nova::flushState();
                /** @phpstan-ignore class.notFound */
                if (class_exists(Cache::class)) {
                    Cache::getInstance()->flush();
                }

                $event->sandbox->forgetInstance(ImpersonatesUsers::class);
            });

            $event->listen(RequestHandled::class, static function ($event) {
                Container::getInstance()->forgetInstance(NovaRequest::class);
            });
        });
    }

    /**
     * Register the package resources such as routes, templates, etc.
     */
    protected function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'nova');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'nova');

        if (Nova::runsMigrations()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        $this->registerRoutes();
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        Route::group(['prefix' => 'nova-api'], function (Router $router) {
            $router->group($this->routeConfiguration(group: 'asset'), function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/asset.php');
            });

            $router->group($this->routeConfiguration(group: 'api'), function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
            });
        });
    }

    /**
     * Get the Nova route group configuration array.
     *
     * @return array{domain: string|null, as: string, middleware: string, excluded_middleware: array<int, class-string>}
     */
    protected function routeConfiguration(string $group): array
    {
        return [
            'domain' => config('nova.domain', null),
            'as' => "nova.{$group}.",
            'middleware' => "nova:{$group}",
            'excluded_middleware' => [SubstituteBindings::class],
        ];
    }

    /**
     * Register the Nova JSON variables.
     */
    protected function registerJsonVariables(): void
    {
        Nova::serving(static function (ServingNova $event) {
            // Load the default Nova translations.
            Nova::translations(
                lang_path("vendor/nova/{$event->app->getLocale()}.json")
            );

            Nova::provideToScript([
                'appName' => Nova::name() ?? config('app.name', 'Laravel Nova'), /** @phpstan-ignore nullCoalesce.expr */
                'timezone' => config('app.timezone', 'UTC'),
                'translations' => static fn () => Nova::allTranslations(),
                'userTimezone' => static fn ($request) => Nova::resolveUserTimezone($request),
                'pagination' => config('nova.pagination', 'links'),
                'locale' => config('app.locale', 'en'),
                'algoliaAppId' => config('services.algolia.appId'),
                'algoliaApiKey' => config('services.algolia.apiKey'),
                'version' => Nova::version(),
            ]);
        });
    }
}
