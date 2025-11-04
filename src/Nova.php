<?php

namespace Laravel\Nova;

use BackedEnum;
use BadMethodCallException;
use Carbon\CarbonInterval;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Nova\Actions\ActionResource;
use Laravel\Nova\Contracts\ImpersonatesUsers;
use Laravel\Nova\Exceptions\ResourceMissingException;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Menu\Menu;
use Laravel\Nova\Support\PendingTranslation;
use Laravel\Prompts\PasswordPrompt;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\TextPrompt;
use ReflectionClass;
use Stringable;
use Symfony\Component\Finder\Finder;

use function Illuminate\Filesystem\join_paths;
use function Orchestra\Sidekick\is_safe_callable;

/**
 * @method static bool runsMigrations()
 */
class Nova
{
    use AuthorizesRequests;
    use Concerns\HandlesRoutes;
    use Concerns\InteractsWithActionEvent;
    use Concerns\InteractsWithAssets;
    use Concerns\InteractsWithEvents;
    use Concerns\InteractsWithFortify;

    /**
     * The registered dashboard names.
     *
     * @var array<int, \Laravel\Nova\Dashboard>
     */
    public static array $dashboards = [];

    /**
     * The registered resource names.
     *
     * @var array<int, class-string<\Laravel\Nova\Resource>>
     */
    public static array $resources = [];

    /**
     * An index of resource names keyed by the model name.
     *
     * @var array<class-string<\Illuminate\Database\Eloquent\Model>, class-string<\Laravel\Nova\Resource>>
     */
    public static array $resourcesByModel = [];

    /**
     * The callback used to create new users via the CLI.
     *
     * @var (callable(mixed...):(\Illuminate\Database\Eloquent\Model))|(\Closure(mixed...):(\Illuminate\Database\Eloquent\Model))|null
     */
    public static $createUserCallback = null;

    /**
     * The callback used to gather new user information via the CLI.
     *
     * @var (callable(\Illuminate\Console\Command):(array<int, \Laravel\Prompts\Prompt|mixed>))|null
     */
    public static $createUserCommandCallback = null;

    /**
     * The callable that resolves the user's locale.
     *
     * @var (callable(\Illuminate\Http\Request):(?string))|null
     */
    public static $userLocaleCallback = null;

    /**
     * The callable that resolves the user's timezone.
     *
     * @var (callable(\Illuminate\Http\Request):(?string))|null
     */
    public static $userTimezoneCallback = null;

    /**
     * All of the registered Nova tools.
     *
     * @var array<int, \Laravel\Nova\Tool>
     */
    public static array $tools = [];

    /**
     * The variables that should be made available on the Nova JavaScript object.
     *
     * @var array<string, mixed>
     */
    public static array $jsonVariables = [];

    /**
     * The callback used to report Nova's exceptions.
     *
     * @var (callable(\Throwable):(void))|null
     */
    public static $reportCallback = null;

    /**
     * Indicates if Nova should register its migrations.
     */
    public static bool $runsMigrations = true;

    /**
     * The translations that should be made available on the Nova JavaScript object.
     *
     * @var array<string, string>
     */
    public static array $translations = [];

    /**
     * The callback used to sort Nova resources in the sidebar.
     *
     * @var (callable(class-string<\Laravel\Nova\Resource>):(mixed))|null
     */
    public static $sortCallback = null;

    /**
     * The debounce amount to use when using global search.
     *
     * @var float
     */
    public static $debounce = 0.5;

    /**
     * The callback used to create Nova's main menu.
     *
     * @var (callable(\Illuminate\Http\Request, \Laravel\Nova\Menu\Menu):(\Laravel\Nova\Menu\Menu|iterable))|null
     */
    public static $mainMenuCallback = null;

    /**
     * The callback used to create Nova's user menu.
     *
     * @var (callable(\Illuminate\Http\Request, \Laravel\Nova\Menu\Menu):(\Laravel\Nova\Menu\Menu|array))|null
     */
    public static $userMenuCallback = null;

    /**
     * The callback used to resolve Nova's footer.
     *
     * @var (callable(\Illuminate\Http\Request):(string|\Stringable))|null
     */
    public static $footerCallback = null;

    /**
     * The callback used to resolve Nova's RTL.
     *
     * @var (\Closure(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool|null
     */
    public static Closure|bool|null $rtlCallback = null;

    /**
     * The callback used to resolve Nova's Breadcrumb.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool
     */
    public static $withBreadcrumbs = false;

    /**
     * The interval (in seconds) to poll for new Nova notifications.
     */
    public static int $notificationPollingInterval = 7;

    /**
     * Indicates if Nova's global search is enabled.
     */
    public static bool $withGlobalSearch = true;

    /**
     * Indicates if Nova's notification center is enabled.
     */
    public static bool $withNotificationCenter = true;

    /**
     * Indicates if Nova's light/dark mode switcher is enabled.
     */
    public static bool $withThemeSwitcher = true;

    /**
     * Indicates if Nova's notification center should show unread count.
     */
    public static bool $showUnreadCountInNotificationCenter = false;

    /**
     * Get the current Nova version.
     */
    public static function version(): string
    {
        return once(static function () {
            $manifest = File::json((string) realpath(join_paths(__DIR__, '..', 'composer.json')));

            $version = $manifest['version'] ?? '4.x';

            return $version.' (Silver Surfer)';
        });
    }

    /**
     * Get the app name utilized by Nova.
     */
    public static function name(): Stringable|string
    {
        return config('nova.name') ?? 'Nova Site';
    }

    /**
     * Run callback when currently serving Nova.
     *
     * @param  callable(\Laravel\Nova\Http\Requests\NovaRequest):mixed  $callback
     * @param  (callable(\Illuminate\Http\Request):(mixed))|null  $default
     */
    public static function whenServing(callable $callback, ?callable $default = null): mixed
    {
        if (app()->bound(NovaRequest::class)) {
            return $callback(app()->make(NovaRequest::class));
        }

        if (\is_callable($default)) {
            return $default(app('request'));
        }

        return null;
    }

    /**
     * Get current user using `nova.guard`.
     *
     * @return \Illuminate\Foundation\Auth\User|null
     */
    public static function user(?Request $request = null)
    {
        $guard = Util::userGuard();

        if (\is_null($request)) {
            return \call_user_func(app('auth')->userResolver(), $guard);
        }

        return $request->user($guard);
    }

    /**
     * Retrieve Nova's Impersonator Implementation.
     */
    public static function impersonator(): Contracts\ImpersonatesUsers
    {
        return app(ImpersonatesUsers::class);
    }

    /**
     * Enable Nova's authentication functionality.
     */
    public static function withAuthentication(): static
    {
        static::routes()->withAuthentication = true;

        return new static;
    }

    /**
     * Enable Nova's password reset functionality.
     */
    public static function withPasswordReset(): static
    {
        static::routes()->withPasswordReset = true;

        return new static;
    }

    /**
     * Boot registered resources.
     */
    public static function bootResources(): void
    {
        static::resourceCollection()
            ->filter(static fn ($resourceClass) => property_exists($resourceClass, 'policy') && ! \is_null($resourceClass::$policy))
            ->each(static function ($resourceClass) {
                Gate::policy($resourceClass, $resourceClass::$policy);
            });
    }

    /**
     * Get the resources available for the given request.
     */
    public static function resourcesForNavigation(Request $request): array
    {
        return static::authorizedResources($request)
            ->availableForNavigation($request)
            ->sortBy(static::sortResourcesWith())
            ->all();
    }

    /**
     * Return Nova's authorized resources.
     *
     * @return \Laravel\Nova\ResourceCollection<int, class-string<\Laravel\Nova\Resource>>
     */
    public static function authorizedResources(Request $request): ResourceCollection
    {
        return static::resourceCollection()->authorized($request);
    }

    /**
     * Return the base collection of Nova resources.
     *
     * @return \Laravel\Nova\ResourceCollection<int, class-string<\Laravel\Nova\Resource>>
     */
    public static function resourceCollection(): ResourceCollection
    {
        return ResourceCollection::make(static::$resources);
    }

    /**
     * Get the sorting strategy to use for Nova resources.
     *
     * @return callable(class-string<\Laravel\Nova\Resource>):mixed
     */
    public static function sortResourcesWith(): callable
    {
        return static::$sortCallback ?? static function ($resourceClass) {
            return $resourceClass::label();
        };
    }

    /**
     * Replace the registered resources with the given resources.
     *
     * @param  array<int, class-string<\Laravel\Nova\Resource>>  $resources
     */
    public static function replaceResources(array $resources): static
    {
        static::$resources = $resources;

        return new static;
    }

    /**
     * Get the available resource groups for the given request.
     */
    public static function groups(Request $request): Collection
    {
        return collect(static::availableResources($request))
            ->map(static function ($resourceClass) {
                /** @var class-string<\Laravel\Nova\Resource> $resourceClass */
                return $resourceClass::group();
            })->unique()->values();
    }

    /**
     * Get the resources available for the given request.
     *
     * @return array<int, class-string<\Laravel\Nova\Resource>>
     */
    public static function availableResources(Request $request): array
    {
        return static::authorizedResources($request)
            ->sortBy(static::sortResourcesWith())
            ->all();
    }

    /**
     * Get the grouped resources available for the given request.
     *
     * @return array<string, \Laravel\Nova\ResourceCollection<int, class-string<\Laravel\Nova\Resource>>>
     */
    public static function groupedResources(Request $request): array
    {
        return ResourceCollection::make(static::availableResources($request))
            ->grouped()
            ->all();
    }

    /**
     * Get the grouped resources available for the given request.
     *
     * @return \Illuminate\Support\Collection<array-key, \Laravel\Nova\ResourceCollection<array-key, class-string<\Laravel\Nova\Resource>>>
     */
    public static function groupedResourcesForNavigation(Request $request): Collection
    {
        return ResourceCollection::make(static::availableResources($request))
            ->groupedForNavigation($request)
            ->filter->count();
    }

    /**
     * Register all of the resource classes in the given directory.
     */
    public static function resourcesIn(string $directory): void
    {
        $namespace = app()->getNamespace();

        /** @var array<int, class-string<\Laravel\Nova\Resource>> $resources */
        $resources = [];

        $gate = app(GateContract::class);

        foreach ((new Finder)->in($directory)->files() as $resource) {
            /** @var class-string<\Laravel\Nova\Resource> $resourceClass */
            $resourceClass = $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($resource->getPathname(), app_path().DIRECTORY_SEPARATOR)
            );

            if (
                is_subclass_of($resourceClass, Resource::class) &&
                ! (new ReflectionClass($resourceClass))->isAbstract() &&
                ! is_subclass_of($resourceClass, ActionResource::class)
            ) {
                $resources[] = $resourceClass;
            }

            if (property_exists($resourceClass, 'policy') && ! \is_null($resourceClass::$policy)) {
                $gate->policy($resourceClass, $resourceClass::$policy);
            }
        }

        static::resources(
            collect($resources)->sort()->all()
        );
    }

    /**
     * Register the given resources.
     *
     * @param  array<int, class-string<\Laravel\Nova\Resource>>  $resources
     */
    public static function resources(array $resources): static
    {
        static::$resources = array_unique(
            array_merge(static::$resources, $resources)
        );

        return new static;
    }

    /**
     * Get a new resource instance with the given model instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Laravel\Nova\Resource<\Illuminate\Database\Eloquent\Model>
     *
     * @throws \Laravel\Nova\Exceptions\ResourceMissingException
     */
    public static function newResourceFromModel($model): Resource
    {
        if (\is_null($resourceClass = static::resourceForModel($model))) {
            throw new ResourceMissingException($model);
        }

        return new $resourceClass($model);
    }

    /**
     * Get the resource class name for a given model class.
     *
     * @param  \Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $class
     * @return class-string<\Laravel\Nova\Resource>|null
     */
    public static function resourceForModel($class): ?string
    {
        if (\is_object($class)) {
            $class = $class::class;
        }

        if (isset(static::$resourcesByModel[$class])) {
            return static::$resourcesByModel[$class];
        }

        $resource = static::resourceCollection()->first(
            static fn ($value) => $value::$model === $class
        );

        return static::$resourcesByModel[$class] = $resource;
    }

    /**
     * Get a resource instance for a given key.
     *
     * @return \Laravel\Nova\Resource|null
     */
    public static function resourceInstanceForKey(?string $key): ?Resource
    {
        if ($resourceClass = static::resourceForKey($key)) {
            return $resourceClass::newResource();
        }

        return null;
    }

    /**
     * Get the resource class name for a given key.
     *
     * @return class-string<\Laravel\Nova\Resource>|null
     */
    public static function resourceForKey(?string $key): ?string
    {
        return static::resourceCollection()->first(
            static fn ($resourceClass) => $resourceClass::uriKey() === $key
        );
    }

    /**
     * Get a fresh model instance for the resource with the given key.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public static function modelInstanceForKey(?string $key)
    {
        $resource = static::resourceForKey($key);

        return $resource ? $resource::newModel() : null;
    }

    /**
     * Create a new user instance.
     */
    public static function createUser(Command $command): mixed
    {
        if (! static::$createUserCallback) {
            static::createUserUsing();
        }

        return \call_user_func(
            static::$createUserCallback,
            ...array_map(static function ($question) {
                return with(value($question), static function ($question) {
                    return $question instanceof Prompt ? $question->prompt() : $question;
                });
            }, \call_user_func(static::$createUserCommandCallback, $command))
        );
    }

    /**
     * Register the callbacks used to create a new user via the CLI.
     *
     * @param  (callable(\Illuminate\Console\Command):(array<int, \Laravel\Prompts\Prompt|mixed>))|null  $createUserCommandCallback
     * @param  (callable(mixed...):(\Illuminate\Database\Eloquent\Model))|(\Closure(mixed...):(\Illuminate\Database\Eloquent\Model))|null  $createUserCallback
     */
    public static function createUserUsing(?callable $createUserCommandCallback = null, ?callable $createUserCallback = null): static
    {
        if (! $createUserCallback) {
            $createUserCallback = $createUserCommandCallback;
            $createUserCommandCallback = null;
        }

        static::$createUserCommandCallback = $createUserCommandCallback ??
            static::defaultCreateUserCommandCallback();

        static::$createUserCallback = $createUserCallback ??
            static::defaultCreateUserCallback();

        return new static;
    }

    /**
     * Get the default callback used for the create user command.
     *
     * @return \Closure(\Illuminate\Console\Command):array<int, \Laravel\Prompts\Prompt|mixed>
     */
    protected static function defaultCreateUserCommandCallback(): callable
    {
        return function ($command) {
            return [
                new TextPrompt(label: 'Name', required: true, validate: ['name' => 'required|min:2']),
                new TextPrompt(label: 'Email Address', required: true, validate: ['email' => 'required|email']),
                new PasswordPrompt(label: 'Password', required: true, validate: ['password' => Password::defaults()]),
            ];
        };
    }

    /**
     * Get the default callback used for creating new Nova users.
     *
     * @return \Closure(string, string, string):\Illuminate\Database\Eloquent\Model
     */
    protected static function defaultCreateUserCallback(): Closure
    {
        return function ($name, $email, $password) {
            $model = Util::userModelOrFallback();

            return tap((new $model)->forceFill([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]))->save();
        };
    }

    /**
     * Set the callable that resolves the user's preferred timezone.
     *
     * @param  (callable(\Illuminate\Http\Request):(?string))|null  $userTimezoneCallback
     */
    public static function userTimezone(?callable $userTimezoneCallback): static
    {
        static::$userTimezoneCallback = $userTimezoneCallback;

        return new static;
    }

    /**
     * Resolve the user's preferred timezone.
     */
    public static function resolveUserTimezone(Request $request): ?string
    {
        if (static::$userTimezoneCallback) {
            return \call_user_func(static::$userTimezoneCallback, $request);
        }

        return null;
    }

    /**
     * Register new tools with Nova.
     *
     * @param  array<int, \Laravel\Nova\Tool>  $tools
     */
    public static function tools(array $tools): static
    {
        static::$tools = array_merge(
            static::$tools,
            $tools
        );

        return new static;
    }

    /**
     * Get the tools registered with Nova.
     *
     * @return array<int, \Laravel\Nova\Tool>
     */
    public static function registeredTools(): array
    {
        return static::$tools;
    }

    /**
     * Boot the available Nova tools.
     */
    public static function bootTools(Request $request): void
    {
        collect(static::availableTools($request))->each->boot();
    }

    /**
     * Get the tools registered with Nova.
     *
     * @return array<int, \Laravel\Nova\Tool>
     */
    public static function availableTools(Request $request): array
    {
        if (\is_null(static::user($request))) {
            return [];
        }

        return collect(static::$tools)->filter->authorize($request)->all();
    }

    /**
     * Get the dashboards registered with Nova.
     *
     * @return array<int, \Laravel\Nova\Dashboard>
     */
    public static function availableDashboards(Request $request): array
    {
        return collect(static::$dashboards)->filter->authorize($request)->all();
    }

    /**
     * Register the dashboards.
     *
     * @param  array<int, \Laravel\Nova\Dashboard>  $dashboards
     */
    public static function dashboards(array $dashboards): static
    {
        static::$dashboards = array_merge(static::$dashboards, $dashboards);

        return new static;
    }

    /**
     * Get the available dashboard for the given request.
     */
    public static function dashboardForKey(string $dashboard, NovaRequest $request): ?Dashboard
    {
        return collect(static::$dashboards)
            ->first(
                static fn ($dash) => $dash->uriKey() === $dashboard && $dash->authorize($request)
            );
    }

    /**
     * Get the available dashboard cards for the given request.
     */
    public static function availableDashboardCardsForDashboard(string $dashboard, NovaRequest $request): Collection
    {
        return with(static::dashboardForKey($dashboard, $request), static function ($dashboard) use ($request) {
            if (\is_null($dashboard)) {
                return collect();
            }

            return collect($dashboard->cards())->filter->authorize($request)->values();
        });
    }

    /**
     * Register the given translations with Nova.
     *
     * @param  array<string, string>|string  $translations
     */
    public static function translations(array|string $translations): static
    {
        if (\is_string($translations)) {
            if (! is_readable($translations)) {
                return new static;
            }

            $translations = json_decode(file_get_contents($translations), true);
        }

        static::$translations = array_merge(static::$translations, $translations);

        return new static;
    }

    /**
     * Get all of the additional translations that should be loaded.
     *
     * @return array<string, string>
     */
    public static function allTranslations(): array
    {
        return static::$translations;
    }

    /**
     * Get the JSON variables that should be provided to the global Nova JavaScript object.
     *
     * @return array<string, mixed>
     */
    public static function jsonVariables(Request $request): array
    {
        return collect(static::$jsonVariables)->map(static function ($variable) use ($request) {
            return is_safe_callable($variable)
                ? \call_user_func($variable, $request)
                : $variable;
        })->all();
    }

    /**
     * Configure Nova to not register its migrations.
     */
    public static function ignoreMigrations(): static
    {
        static::$runsMigrations = false;

        return new static;
    }

    /**
     * Humanize the given value into a proper name.
     */
    public static function humanize(object|string $value): string
    {
        if (! $value instanceof BackedEnum) {
            return Str::headline(match (true) {
                \is_object($value) => class_basename($value::class),
                class_exists($value, false) => class_basename($value),
                default => $value
            });
        }

        if (method_exists($value, 'name')) {
            return $value->name();
        } elseif (method_exists($value, 'label')) {
            return $value->label();
        }

        return Str::title(str_replace('_', ' ', $value->name));
    }

    /**
     * Register the callback used to set a custom Nova error reporter.
     *
     * @param  (callable(\Throwable):(void))|null  $callback
     */
    public static function report(?callable $callback): static
    {
        static::$reportCallback = $callback;

        return new static;
    }

    /**
     * Provide additional variables to the global Nova JavaScript object.
     *
     * @param  array<string, mixed>  $variables
     */
    public static function provideToScript(array $variables): static
    {
        if (empty(static::$jsonVariables)) {
            $userId = Auth::guard(config('nova.guard'))->id() ?? null;

            static::$jsonVariables = [
                'debug' => static fn () => config('app.debug') || app()->environment('testing'),
                'logo' => static::logo(),
                'brandColors' => static::brandColors(),
                'brandColorsCSS' => static::brandColorsCSS(),
                'rtlEnabled' => static fn () => static::rtlEnabled(),
                'breadcrumbsEnabled' => static fn () => static::breadcrumbsEnabled(),
                'globalSearchEnabled' => static function () {
                    return static::globalSearchIsEnabled() && static::hasGloballySearchableResources();
                },
                'notificationCenterEnabled' => static fn () => static::$withNotificationCenter,
                'hasGloballySearchableResources' => static fn () => static::hasGloballySearchableResources(),
                'themeSwitcherEnabled' => static fn () => static::$withThemeSwitcher,
                'showUnreadCountInNotificationCenter' => static fn () => static::$showUnreadCountInNotificationCenter,
                'withAuthentication' => static::routes()->withAuthentication,
                'withPasswordReset' => static::routes()->withPasswordReset,
                'customLoginPath' => static fn () => static::routes()->loginPath ?? false,
                'customLogoutPath' => static fn () => static::routes()->logoutPath ?? false,
                'forgotPasswordPath' => static fn () => static::routes()->forgotPasswordPath ?? false,
                'resetPasswordPath' => static fn () => static::routes()->resetPasswordPath ?? false,
                'debounce' => static::$debounce * 1000,
                'initialPath' => static fn ($request) => static::resolveInitialPath($request),
                'base' => static::path(),
                'userId' => $userId,
                'mainMenu' => static function ($request) use ($userId) {
                    return ! \is_null($userId) ? Menu::wrap(self::resolveMainMenu($request)) : [];
                },
                'userMenu' => static function ($request) use ($userId) {
                    return ! \is_null($userId) ? Menu::wrap(self::resolveUserMenu($request)) : Menu::make();
                },
                'notificationPollingInterval' => static::$notificationPollingInterval * 1000,
                'resources' => static fn ($request) => static::resourceInformation($request),
                'footer' => static fn ($request) => self::resolveFooter($request),
                'defaultAuthentication' => static fn () => static::routes()->defaultAuthentication(),
                'fortifyFeatures' => static fn () => config('fortify.features', []),
                'fortifyOptions' => static fn () => config('fortify-options', []),
            ];
        }

        static::$jsonVariables = array_merge(static::$jsonVariables, $variables);

        return new static;
    }

    /**
     * Check to see if Nova is valid for the configured license key.
     */
    public static function checkLicenseValidity(): bool
    {
        return Cache::remember(
            'nova_valid_license_key',
            3600,
            static fn () => true
        );
    }

    /**
     * Check to see if Nova is valid for the configured license key.
     */
    public static function checkLicense(): ClientResponse
    {
        return Http::post('https://nova.laravel.com/api/license-check', [
            'url' => request()->getHost(),
            'key' => config('nova.license_key', ''),
        ]);
    }

    /**
     * Get the logo that is configured for the Nova admin.
     */
    public static function logo(): ?string
    {
        $logo = config('nova.brand.logo');

        if (! empty($logo) && file_exists(realpath($logo))) {
            return file_get_contents(realpath($logo));
        }

        return $logo;
    }

    /**
     * Get Nova's content direction.
     */
    public static function rtlEnabled(): bool
    {
        if (static::$rtlCallback instanceof Closure) {
            static::$rtlCallback = value(static::$rtlCallback, app(NovaRequest::class));
        }

        return (bool) static::$rtlCallback;
    }

    /**
     * Enable RTL content direction.
     *
     * @param  (\Closure(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool  $rtlCallback
     */
    public static function enableRTL(Closure|bool $rtlCallback = true): static
    {
        static::$rtlCallback = $rtlCallback;

        return new static;
    }

    /**
     * Determine if there are any globally searchable resources.
     */
    public static function hasGloballySearchableResources(): bool
    {
        return collect(static::globallySearchableResources(app(NovaRequest::class)))->count() > 0;
    }

    /**
     * Determine if global search is enabled.
     */
    public static function globalSearchIsEnabled(): bool
    {
        return static::$withGlobalSearch;
    }

    /**
     * Get the resources available for the given request.
     *
     * @return array<int, class-string<\Laravel\Nova\Resource>>
     */
    public static function globallySearchableResources(Request $request): array
    {
        return static::authorizedResources($request)
            ->globallySearchable()
            ->sortBy(static::sortResourcesWith())
            ->all();
    }

    /**
     * Resolve the main menu for Nova.
     */
    public static function resolveMainMenu(Request $request): Menu|iterable
    {
        $defaultMenu = static::defaultMainMenu($request);

        if (! \is_null(static::$mainMenuCallback)) {
            return \call_user_func(static::$mainMenuCallback, $request, $defaultMenu);
        }

        return $defaultMenu;
    }

    /**
     * Resolve the default main menu for Nova.
     */
    public static function defaultMainMenu(Request $request): Menu
    {
        return Menu::make(with(collect(static::availableTools($request)), static function ($tools) use ($request) {
            return $tools->map(static fn ($tool) => $tool->menu($request));
        })->filter()->values()->all());
    }

    /**
     * Resolve the user menu for Nova.
     */
    public static function resolveUserMenu(Request $request): Menu
    {
        $defaultMenu = static::defaultUserMenu($request);

        if (! \is_null(static::$userMenuCallback)) {
            return \call_user_func(static::$userMenuCallback, $request, $defaultMenu);
        }

        return $defaultMenu;
    }

    /**
     * Resolve the default user menu for Nova.
     */
    public static function defaultUserMenu(Request $request): Menu
    {
        return Menu::make([
            //
        ]);
    }

    /**
     * Get meta data information about all resources for client side consumption.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function resourceInformation(Request $request): array
    {
        return static::resourceCollection()->map(static function ($resourceClass) use ($request) {
            /** @var class-string<\Laravel\Nova\Resource> $resourceClass */
            return array_merge([
                'uriKey' => $resourceClass::uriKey(),
                'label' => $resourceClass::label(),
                'singularLabel' => $resourceClass::singularLabel(),
                'createButtonLabel' => $resourceClass::createButtonLabel(),
                'updateButtonLabel' => $resourceClass::updateButtonLabel(),
                'authorizedToCreate' => $resourceClass::authorizedToCreate($request),
                'searchable' => $resourceClass::searchable(),
                'tableStyle' => $resourceClass::tableStyle(),
                'showColumnBorders' => $resourceClass::showColumnBorders(),
                'debounce' => $resourceClass::$debounce * 1000,
                'clickAction' => $resourceClass::clickAction(),

                /** @deprecated */
                'perPageOptions' => $resourceClass::perPageOptions(),
            ], $resourceClass::additionalInformation($request));
        })->values()->all();
    }

    /**
     * Dynamically proxy static method calls.
     *
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic(string $method, array $parameters)
    {
        if (! property_exists(\get_called_class(), $method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        return static::${$method};
    }

    /**
     * Register the callback used to sort Nova resources in the sidebar.
     *
     * @param  callable(class-string<\Laravel\Nova\Resource>):mixed  $callback
     */
    public static function sortResourcesBy(callable $callback): static
    {
        static::$sortCallback = $callback;

        return new static;
    }

    /**
     * Return the debounce amount to use when using global search.
     */
    public static function globalSearchDebounce(CarbonInterval|int $debounce): static
    {
        static::$debounce = $debounce instanceof CarbonInterval
            ? $debounce->totalSeconds
            : $debounce;

        return new static;
    }

    /**
     * Set the main menu for Nova.
     *
     * @param  callable(\Illuminate\Http\Request, \Laravel\Nova\Menu\Menu):(\Laravel\Nova\Menu\Menu|iterable)  $callback
     */
    public static function mainMenu(callable $callback): static
    {
        static::$mainMenuCallback = $callback;

        return new static;
    }

    /**
     * Set the main menu for Nova.
     *
     * @param  callable(\Illuminate\Http\Request, \Laravel\Nova\Menu\Menu):(\Laravel\Nova\Menu\Menu|array)  $userMenuCallback
     */
    public static function userMenu(callable $userMenuCallback): static
    {
        static::$userMenuCallback = $userMenuCallback;

        return new static;
    }

    /**
     * Enable Breadcrumb Menu.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool  $withBreadcrumbs
     */
    public static function withBreadcrumbs(callable|bool $withBreadcrumbs = true): static
    {
        static::$withBreadcrumbs = $withBreadcrumbs;

        return new static;
    }

    /**
     * Determine if Nova's breadcrumbs menu should be displayed.
     */
    public static function breadcrumbsEnabled(): bool
    {
        return \is_callable(static::$withBreadcrumbs)
            ? \call_user_func(static::$withBreadcrumbs, app(NovaRequest::class))
            : static::$withBreadcrumbs;
    }

    /**
     * Set the polling interval used for Nova's notifications.
     */
    public static function notificationPollingInterval(CarbonInterval|int $seconds): static
    {
        static::$notificationPollingInterval = $seconds instanceof CarbonInterval
            ? $seconds->totalSeconds
            : $seconds;

        return new static;
    }

    /**
     * Set the footer text used for Nova.
     *
     * @param  callable(\Illuminate\Http\Request):(\Stringable|string)  $footerCallback
     */
    public static function footer(callable $footerCallback): static
    {
        static::$footerCallback = $footerCallback;

        return new static;
    }

    /**
     * Resolve the footer used for Nova.
     */
    public static function resolveFooter(Request $request): string
    {
        if (! \is_null(static::$footerCallback)) {
            return (string) \call_user_func(static::$footerCallback, $request);
        }

        return static::defaultFooter($request);
    }

    /**
     * Resolve the default footer text used for Nova.
     */
    public static function defaultFooter(Request $request): string
    {
        return Blade::render('
            <p class="text-center">Powered by <a class="link-default" href="https://nova.laravel.com">Laravel Nova</a> · v{!! $version !!}</p>
            <p class="text-center">&copy; {!! $year !!} Laravel Holdings Inc.</p>
        ', [
            'version' => static::version(),
            'year' => date('Y'),
        ]);
    }

    /**
     * Disable global search globally.
     */
    public static function withoutGlobalSearch(): static
    {
        static::$withGlobalSearch = false;

        return new static;
    }

    /**
     * Disable notification center.
     */
    public static function withoutNotificationCenter(): static
    {
        static::$withNotificationCenter = false;

        return new static;
    }

    /**
     * Disable light/dark mode theme switching.
     */
    public static function withoutThemeSwitcher(): static
    {
        static::$withThemeSwitcher = false;

        return new static;
    }

    /**
     * Return Nova's custom brand colors.
     */
    public static function brandColors(): array
    {
        return collect(config('nova.brand.colors'))
            ->reject(static fn ($value) => \is_null($value))
            ->all();
    }

    /**
     * Return the CSS used to override Nova's brand colors.
     */
    public static function brandColorsCSS(): string
    {
        return Blade::render('
:root {
@foreach($colors as $key => $value)
    --colors-primary-{{ $key }}: {{ $value }};
@endforeach
}', [
            'colors' => static::brandColors(),
        ]);
    }

    /**
     * Set the callable that resolves the user's preferred locale.
     *
     * @param  (callable(\Illuminate\Http\Request):(?string))|null  $userLocaleCallback
     */
    public static function userLocale(?callable $userLocaleCallback): static
    {
        static::$userLocaleCallback = $userLocaleCallback;

        return new static;
    }

    /**
     * Resolve the user's preferred locale.
     */
    public static function resolveUserLocale(Request $request): string
    {
        $locale = null;

        if (static::$userLocaleCallback) {
            $locale = \call_user_func(static::$userLocaleCallback, $request);
        }

        return str_replace('_', '-', $locale ?? app()->getLocale());
    }

    /**
     * Translate the given message.
     *
     * @param  array<string, string>  $replace
     */
    public static function __(PendingTranslation|string|null $key = null, array $replace = [], ?string $locale = null): PendingTranslation
    {
        if ($key instanceof PendingTranslation) {
            return $key;
        }

        return new PendingTranslation($key, $replace, $locale);
    }

    /**
     * Enable unread notifications count in the notification center.
     */
    public static function showUnreadCountInNotificationCenter(): static
    {
        static::$showUnreadCountInNotificationCenter = true;

        return new static;
    }
}
