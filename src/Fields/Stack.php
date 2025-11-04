<?php

namespace Laravel\Nova\Fields;

use Illuminate\Support\Collection;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * @method static static make(\Stringable|string $name, string|array|null $attribute = null, array $lines = [])
 */
class Stack extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'stack-field';

    /**
     * Indicates if the element should be shown on the creation view.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool
     */
    public $showOnCreation = false;

    /**
     * Indicates if the element should be shown on the update view.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest, mixed):(bool))|bool
     */
    public $showOnUpdate = false;

    /**
     * The contents of the Stack field.
     */
    public Collection $lines;

    /**
     * Create a new Stack field.
     *
     * @param  \Stringable|string  $name
     * @param  string|array<int, class-string<\Laravel\Nova\Fields\Field>|callable>|null  $attribute
     * @param  iterable<int, class-string<\Laravel\Nova\Fields\Field>|callable>  $lines
     */
    public function __construct($name, mixed $attribute = null, iterable $lines = [])
    {
        if (\is_array($attribute)) {
            $lines = $attribute;
            $attribute = null;
        }

        parent::__construct($name, $attribute);

        $this->lines = Collection::make($lines);
    }

    /**
     * Resolve the field's value for display.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object  $resource
     */
    #[\Override]
    public function resolveForDisplay($resource, ?string $attribute = null): void
    {
        $this->prepareLines($resource, $attribute);
    }

    /**
     * Prepare each line for serialization.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object  $resource
     */
    public function prepareLines($resource, ?string $attribute = null): void
    {
        $this->ensureLinesAreResolveable();

        $request = app(NovaRequest::class);

        $this->lines = $this->lines->filter(static function ($field) use ($request, $resource) {
            if (! $field->authorizedToSee($request)) {
                return false;
            }

            /** @var \Laravel\Nova\Fields\Field $field */
            if ($request->isResourceIndexRequest()) {
                return $field->isShownOnIndex($request, $resource);
            }

            return $field->isShownOnDetail($request, $resource);
        })
            ->values()
            ->each->resolveForDisplay($resource, $attribute);
    }

    /**
     * Get field lines.
     */
    public function fields(): Collection
    {
        return $this->lines->whereInstanceOf(Field::class);
    }

    /**
     * Ensure that each line for the field is resolvable.
     */
    protected function ensureLinesAreResolveable(): void
    {
        $this->lines = $this->lines->map(static function ($line) {
            if (\is_callable($line)) {
                return Line::make('Anonymous', $line);
            }

            return $line;
        });
    }

    /**
     * Prepare the stack for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'lines' => $this->lines->all(),
        ]);
    }
}
