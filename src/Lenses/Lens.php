<?php

namespace Laravel\Nova\Lenses;

use ArrayAccess;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\ConditionallyLoadsAttributes;
use Illuminate\Http\Resources\DelegatesToResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JsonSerializable;
use Laravel\Nova\AuthorizedToSee;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Makeable;
use Laravel\Nova\Nova;
use Laravel\Nova\ProxiesCanSeeToGate;
use Laravel\Nova\ResolvesActions;
use Laravel\Nova\ResolvesCards;
use Laravel\Nova\ResolvesFilters;
use Laravel\Nova\SupportsPolling;
use stdClass;

abstract class Lens implements ArrayAccess, JsonSerializable, UrlRoutable
{
    use AuthorizedToSee;
    use ConditionallyLoadsAttributes;
    use DelegatesToResource;
    use Makeable;
    use ProxiesCanSeeToGate;
    use ResolvesActions;
    use ResolvesCards;
    use ResolvesFilters;
    use SupportsPolling;

    /**
     * The displayable name of the lens.
     *
     * @var string
     */
    public $name;

    /**
     * The underlying model resource instance.
     *
     * @var \Illuminate\Database\Eloquent\Model|\stdClass
     */
    public $resource;

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [];

    /**
     * The pagination per-page options used for this lens.
     *
     * @var int|array<int, int>|null
     */
    public static $perPageOptions = null;

    /**
     * Execute the query for the lens.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\Builder|\Illuminate\Contracts\Pagination\Paginator
     */
    abstract public static function query(LensRequest $request, Builder $query);

    /**
     * Get the fields displayed by the lens.
     *
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    abstract public function fields(NovaRequest $request);

    /**
     * Create a new lens instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $resource
     */
    public function __construct($resource = null)
    {
        $this->resource = $resource ?: new stdClass;
    }

    /**
     * Set the resource of the lens.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $resource
     * @return $this
     */
    public function setResource($resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Get the displayable name of the lens.
     *
     * @return string
     */
    public function name()
    {
        return $this->name ?: Nova::humanize($this);
    }

    /**
     * Get the URI key for the lens.
     *
     * @return string
     */
    public function uriKey()
    {
        return Str::slug($this->name(), '-', null);
    }

    /**
     * Get the actions available on the lens.
     *
     * @return array<int, \Laravel\Nova\Actions\Action>
     */
    public function actions(NovaRequest $request)
    {
        return $request->newResourceWith(
            $this->resource instanceof Model ? $this->resource : $request->model()
        )->actions($request);
    }

    /**
     * Resolve the given fields to their values.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function resolveFields(NovaRequest $request)
    {
        /** @phpstan-ignore return.type */
        return $this->availableFields($request)
            ->filterForIndex($request, $this->resource)
            ->withoutListableFields()
            ->authorized($request)
            ->resolveForDisplay($this->resource);
    }

    /**
     * Resolve the filterable fields.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field&\Laravel\Nova\Contracts\FilterableField>
     */
    public function filterableFields(NovaRequest $request)
    {
        return $this->availableFields($request)
            ->flattenStackedFields()
            ->withOnlyFilterableFields()
            ->authorized($request);
    }

    /**
     * Get the fields that are available for the given request.
     *
     * @return \Laravel\Nova\Fields\FieldCollection
     */
    public function availableFields(NovaRequest $request)
    {
        return new FieldCollection(array_values($this->filter($this->fields($request))));
    }

    /**
     * Determine if this resource is searchable.
     *
     * @return bool
     */
    public static function searchable()
    {
        return ! empty(static::searchableColumns());
    }

    /**
     * Get the searchable columns for the lens.
     *
     * @return array
     */
    public static function searchableColumns()
    {
        return static::$search;
    }

    /**
     * The pagination per-page options configured for this lens.
     *
     * @return array<int, int>|null
     */
    public static function perPageOptions()
    {
        return transform(
            static::$perPageOptions,
            static fn ($perPageOptions) => Arr::wrap($perPageOptions),
            null,
        );
    }

    /**
     * Prepare the lens for JSON serialization using the given fields.
     *
     * @param  \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>  $fields
     * @return array
     */
    protected function serializeWithId(FieldCollection $fields)
    {
        return [
            'id' => $fields->whereInstanceOf(ID::class)->first() ?: ID::forModel($this->resource),
            'fields' => $fields->all(),
        ];
    }

    /**
     * Prepare the lens for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name(),
            'uriKey' => $this->uriKey(),
        ];
    }
}
