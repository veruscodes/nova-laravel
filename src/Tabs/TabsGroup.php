<?php

namespace Laravel\Nova\Tabs;

use Illuminate\Http\Resources\MergeValue;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Nova\Contracts\ListableField;
use Laravel\Nova\Panel;

/**
 * @phpstan-import-type TFields from \Laravel\Nova\Resource
 * @phpstan-import-type TPanelFields from \Laravel\Nova\Fields\FieldMergeValue
 *
 * @phpstan-type TGroupFields iterable<int, TFields|\Laravel\Nova\Tabs\Tab>
 *
 * @method static static make(\Stringable|string|null $name = null, callable|iterable $fields = [], ?string $attribute = null)
 */
class TabsGroup extends Panel
{
    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'tabs-panel';

    /**
     * Determine if title should be shown.
     */
    public bool $showTitle = true;

    /**
     * List of tabs.
     *
     * @var array<int, \Laravel\Nova\Tabs\Tab>
     */
    public array $tabs = [];

    /**
     * Cached tab counts.
     */
    public int $tabsCount = 0;

    /**
     * The tab's name (readonly).
     */
    public readonly string $originalName;

    /**
     * Create a new panel instance.
     *
     * @param  \Stringable|string|null  $name
     * @param  (callable():(iterable))|iterable  $fields
     *
     * @phpstan-param (callable():(TGroupFields))|TGroupFields  $fields
     */
    public function __construct($name = null, callable|iterable $fields = [], ?string $attribute = null)
    {
        if (\is_null($attribute)) {
            $attribute = Str::random(16);
        }

        if (\is_null($name)) {
            $this->showTitle = false;
            $name = $attribute;
        }

        $this->originalName = $name;

        parent::__construct($name, $fields, $attribute);
    }

    /**
     * Hydrate panel from fields.
     *
     * @internal
     */
    public static function hydrate(self $panel, iterable $fields): void
    {
        $panel->name = $fields[0]->panel;

        tap($fields[0]->panel, static function ($original) use ($panel) {
            $panel->showTitle = $original->showTitle;
            $panel->showToolbar = $original->showToolbar;
            $panel->attribute = $original->attribute;
        });
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function prepareFields(callable|iterable $fields): iterable
    {
        $this->convertFieldsToTabs($fields)
            ->each(function ($tab) {
                /** @var \Laravel\Nova\Tabs\Tab $tab */
                $this->addFields($tab);
            });

        return $this->data ?? [];
    }

    /**
     * Convert fields to tabs.
     *
     * @return \Illuminate\Support\Collection<int, \Laravel\Nova\Tabs\Tab>
     */
    protected function convertFieldsToTabs(callable|iterable $fields): Collection
    {
        $fieldsCollection = collect(
            \is_callable($fields) ? \call_user_func($fields) : $fields
        );

        return $fieldsCollection->map(function ($fields, $key) {
            /** @var string|int $key */
            if ($fields instanceof Tab) {
                return $fields;
            }

            $this->tabsCount++;

            if ($fields instanceof Panel) {
                return Tab::make(
                    $fields->name, $fields->data,
                )->withPosition($this->tabsCount);
            }

            if (! \is_array($fields)) {
                return Tab::make(
                    $fields->name, [$fields],
                )->withPosition($this->tabsCount);
            }

            return Tab::make(
                $key, $fields
            )->withPosition($this->tabsCount);
        })->values();
    }

    /**
     * Add fields to the Tab.
     *
     * @internal
     *
     * @return $this
     */
    public function addFields(Tab $tab)
    {
        $this->tabs[] = $tab;

        foreach ($tab->data as $field) {
            /** @var \Laravel\Nova\Panel|\Laravel\Nova\Fields\Field $field */
            if ($field instanceof Panel) {
                /** @phpstan-ignore property.notFound */
                $field->panel = $this;

                $this->addFields(
                    Tab::make(
                        $field->name, $field->data
                    )->withPosition(++$this->tabsCount)
                );

                continue;
            }

            /** @phpstan-ignore instanceof.alwaysFalse */
            if ($field instanceof MergeValue) {
                if (! isset($field->panel)) {
                    $field->panel = $this;
                }

                $this->addFields(
                    Tab::mutate($tab, $field->data)
                );

                continue;
            }

            $field->panel = $this;

            $meta = [
                'tab' => [
                    'name' => $tab->name,
                    'attribute' => $tab->attribute,
                    'position' => $tab->position,
                    'meta' => Arr::except($tab->jsonSerialize(), ['fields', 'attribute']),
                    'listable' => false,
                ],
            ];

            if ($field instanceof ListableField) {
                $meta['listable'] = false;
                $meta['tab']['listable'] = true;
            }

            $field->withMeta($meta);

            $this->data[] = $field;
        }

        return $this;
    }

    /**
     * Prepare the panel for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'showTitle' => $this->showTitle,
            'attribute' => $this->attribute,
        ]);
    }
}
