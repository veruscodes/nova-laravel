<?php

namespace Laravel\Nova\Menu;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use JsonSerializable;
use Laravel\Nova\AuthorizedToSee;
use Laravel\Nova\Contracts\Filter as FilterContract;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Filters\FilterEncoder;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Makeable;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;
use Laravel\Nova\URL;
use Laravel\Nova\WithBadge;
use Laravel\Nova\WithComponent;
use Stringable;

/**
 * @method static static make(\Stringable|string $name, string|null $path = null)
 */
class MenuItem implements JsonSerializable
{
    use AuthorizedToSee;
    use Conditionable;
    use Macroable;
    use Makeable;
    use WithBadge;
    use WithComponent;

    /**
     * The menu's component.
     *
     * @var string
     */
    public $component = 'menu-item';

    /**
     * The menu's request method (GET, POST, PUT, PATCH, DELETE).
     *
     * @var string
     */
    public $method = 'GET';

    /**
     * The menu's data.
     *
     * @var array<string, string>|null
     */
    public $data = null;

    /**
     * The menu's headers.
     *
     * @var array<string, string>|null
     */
    public $headers = null;

    /**
     * Indicate whether the menu's resolve to an external URL.
     *
     * @var bool
     */
    public $external = false;

    /**
     * The target value for external link.
     *
     * @var string|null
     */
    public $target = null;

    /**
     * The active menu callback.
     *
     * @var (callable(\Illuminate\Http\Request, \Laravel\Nova\URL):bool)|bool|null
     */
    public $activeMenuCallback = null;

    /**
     * The resource class name.
     *
     * @var class-string<\Laravel\Nova\Resource>|null
     */
    public $resource = null;

    /**
     * The filters for the menu item.
     */
    public Collection $filters;

    /**
     * Construct a new Menu Item instance.
     */
    public function __construct(
        public Stringable|string $name,
        public ?string $path = null
    ) {
        $this->filters = Collection::make();
    }

    /**
     * Create a menu item from a resource class.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     * @return static
     */
    public static function resource(string $resourceClass)
    {
        return static::make($resourceClass::label())
            ->forResource($resourceClass)
            ->path('/resources/'.$resourceClass::uriKey())
            ->activeWhen(static fn ($request, $url) => ! $request->routeIs('nova.pages.lens') ? $url->active() : false)
            ->canSee(static fn ($request) => $resourceClass::availableForNavigation($request) && $resourceClass::authorizedToViewAny($request));
    }

    /**
     * Create a menu item from a lens class.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     * @param  class-string<\Laravel\Nova\Lenses\Lens>  $lensClass
     * @return static
     */
    public static function lens(string $resourceClass, string $lensClass)
    {
        return with(new $lensClass, static function ($lens) use ($resourceClass) {
            return static::make($lens->name())
                ->path('/resources/'.$resourceClass::uriKey().'/lens/'.$lens->uriKey())
                ->canSee(static fn ($request) => $lens->authorizedToSee($request));
        });
    }

    /**
     * Create a menu item from a resource with a set of filters.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     * @param  mixed|null  $value
     * @return static
     */
    public static function filter(Stringable|string $name, string $resourceClass, FilterContract|string|null $filter = null, $value = null)
    {
        $item = static::make($name)
            ->forResource($resourceClass);

        if ($filter) {
            $item->applies($filter, $value);
        }

        return $item->activeWhen(static fn ($request, $url) => "/{$request->path()}?{$request->getQueryString()}" === (string) $url)
            ->canSee(static fn ($request) => $resourceClass::availableForNavigation($request) && $resourceClass::authorizedToViewAny($request));
    }

    /**
     * Apply a filter to the menu item.
     *
     * @return $this
     */
    public function applies(FilterContract|string $filter, mixed $value)
    {
        // If the filter is an actual filter instance, and not a filter generated from
        // a `filterable` field, let's ensure the user is authorized to see it.
        // If not, don't push the filter into the filter's collection.
        if ($filter instanceof FilterContract && ! $filter->authorizedToSee(app(NovaRequest::class))) {
            return $this;
        }

        $this->filters->push([
            'class' => $filter instanceof FilterContract ? $filter->key() : $filter,
            'value' => $value,
        ]);

        $queryString = $this->queryString();

        $path = '/resources/'.$this->resource::uriKey().'?'.$queryString;

        return $this->path($path);
    }

    /**
     * Set the resource to be used for the menu item.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     * @return $this
     */
    protected function forResource(string $resourceClass)
    {
        $this->resource = $resourceClass;

        return $this;
    }

    /**
     * Return the query string for a filtered resource menu item.
     */
    protected function queryString(): string
    {
        return Arr::query([
            $this->resource::uriKey().'_filter' => $this->encodedFilters(),
        ]);
    }

    /**
     * Get the encoded filter.
     */
    public function encodedFilters(): string
    {
        return new FilterEncoder($this->filters->all());
    }

    /**
     * Set menu's path.
     *
     * @return $this
     */
    public function path(URL|string|null $href)
    {
        $this->path = $href;

        return $this;
    }

    /**
     * Create a menu from dashboard class.
     *
     * @param  class-string<\Laravel\Nova\Dashboard>  $dashboard
     * @return static
     */
    public static function dashboard(string $dashboard)
    {
        return with(new $dashboard, static function ($dashboard) {
            return static::make(
                $dashboard->label(),
                '/dashboards/'.$dashboard->uriKey()
            )->canSee(static fn ($request) => $dashboard->authorizedToSee($request));
        });
    }

    /**
     * Create menu to an internal Nova path.
     *
     * @return static
     */
    public static function link(Stringable|string $name, string $path)
    {
        return new static($name, $path);
    }

    /**
     * Create menu to an external URL.
     *
     * @return static
     */
    public static function externalLink(Stringable|string $name, string $path)
    {
        return (new static($name, $path))->external();
    }

    /**
     * Marked as external url.
     *
     * @return $this
     */
    public function external()
    {
        $this->external = true;

        return $this;
    }

    /**
     * Set the menu's target to open in a new tab.
     *
     * @return $this
     */
    public function openInNewTab()
    {
        $this->target = '_blank';

        return $this;
    }

    /**
     * Set menu's method, and optionally data or headers.
     *
     * @param  array<string, mixed>|null  $data
     * @param  array<string, string>|null  $headers
     * @return $this
     */
    public function method(string $method, ?array $data = null, ?array $headers = null)
    {
        if (! \in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
            throw new InvalidArgumentException('Only supports GET, POST, PUT, PATCH or DELETE method');
        }

        $this->method = $method;

        return $this->data($data)->headers($headers);
    }

    /**
     * Set menu's method, and optionally data or headers. This request will be handled via Inertia.visit().
     *
     * @param  array<string, mixed>|null  $data
     * @param  array<string, string>|null  $headers
     * @return static
     */
    public function inertia(string $method = 'GET', ?array $data = null, ?array $headers = null)
    {
        if ($method !== 'GET') {
            $headers = Arr::wrap($headers);
        }

        return $this->method($method, $data, $headers);
    }

    /**
     * Set menu's headers.
     *
     * @param  array<string, string>|null  $headers
     * @return $this
     */
    public function headers(?array $headers = null)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Set menu's data.
     *
     * @param  array<string, string>|null  $data
     * @return $this
     */
    public function data(?array $data = null)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set menu's name.
     *
     * @return $this
     */
    public function name(Stringable|string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Determine the default active URL state.
     *
     * @param  (callable(\Illuminate\Http\Request, \Laravel\Nova\URL):bool)|bool  $activeMenuCallback
     * @return $this
     */
    public function activeWhen(callable|bool $activeMenuCallback)
    {
        $this->activeMenuCallback = $activeMenuCallback;

        return $this;
    }

    /**
     * Determine the default active URL state.
     *
     * @param  (callable(\Illuminate\Http\Request, \Laravel\Nova\URL):bool)|bool  $activeMenuCallback
     * @return $this
     */
    public function activeUnless(callable|bool $activeMenuCallback)
    {
        $this->activeMenuCallback = static function ($request, $url) use ($activeMenuCallback) {
            return value($activeMenuCallback, $request, $url) === false;
        };

        return $this;
    }

    /**
     * Prepare the menu for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $url = URL::make($this->path, $this->external);

        $activeMenuCallback = $this->activeMenuCallback ?? static fn ($request, $url) => $url->active();

        return [
            'name' => Nova::__($this->name),
            'component' => $this->component,
            'path' => (string) $url,
            'external' => $this->external,
            'target' => $this->target,
            'method' => $this->method,
            'data' => $this->data,
            'headers' => $this->headers,
            'active' => value($activeMenuCallback, request(), $url),
            'badge' => $this->resolveBadge(),
        ];
    }
}
