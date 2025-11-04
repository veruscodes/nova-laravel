<?php

namespace Laravel\Nova\Tabs;

use Illuminate\Support\Str;
use JsonSerializable;
use Laravel\Nova\Fields\FieldMergeValue;
use Laravel\Nova\Makeable;
use Stringable;

/**
 * @phpstan-import-type TFields from \Laravel\Nova\Resource
 * @phpstan-import-type TPanelFields from \Laravel\Nova\Fields\FieldMergeValue
 * @phpstan-import-type TGroupFields from \Laravel\Nova\Tabs\TabsGroup
 *
 * @method static static make(\Stringable|string $name, callable|array $fields, ?string $attribute = null)
 */
class Tab extends FieldMergeValue implements JsonSerializable
{
    use Makeable;

    /**
     * The name of the tab.
     */
    public Stringable|string $name;

    /**
     * The unique identifier of the tab.
     */
    public string $attribute;

    /**
     * The position of the tab.
     */
    public int $position = 0;

    /**
     * Construct a new tab instance.
     *
     * @param  \Stringable|string  $name
     * @param  (callable():(iterable))|iterable  $fields
     *
     * @phpstan-param (callable():(TPanelFields))|TPanelFields $fields
     */
    public function __construct(
        $name,
        callable|iterable $fields,
        ?string $attribute = null,
    ) {
        $this->name = $name;
        $this->attribute = $attribute ?? Str::slug($name);

        parent::__construct($this->prepareFields($fields));
    }

    /**
     * Make a new tabs panel instance.
     *
     * @param  \Stringable|string|null  $name
     * @param  (callable():(iterable))|iterable  $fields
     *
     * @phpstan-param (callable():(TGroupFields))|TGroupFields $fields
     *
     * @return \Laravel\Nova\Tabs\TabsGroup
     */
    public static function group($name = null, callable|iterable $fields = [], ?string $attribute = null)
    {
        return TabsGroup::make($name, $fields, $attribute);
    }

    /**
     * Hydrate tab from fields.
     *
     * @internal
     *
     * @param  (callable():(iterable))|iterable  $fields
     *
     * @phpstan-param (callable():(TPanelFields))|TPanelFields $fields
     *
     * @return static
     */
    public static function mutate(self $tab, callable|iterable $fields)
    {
        return tap(new static($tab->name, $fields), static function ($newTab) use ($tab) {
            $newTab->withAttribute($tab->attribute);
        });
    }

    /**
     * Set the unique identifier for the tab.
     *
     * @return $this
     */
    public function withAttribute(string $attribute)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * Set the position for the tab.
     *
     * @internal
     *
     * @return $this
     */
    public function withPosition(int $position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Prepare the tab for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'fields' => $this->data,
            'position' => $this->position,
            'attribute' => $this->attribute,
        ];
    }
}
