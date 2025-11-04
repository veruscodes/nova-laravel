<?php

namespace Laravel\Nova\Menu;

use Illuminate\Support\Traits\Macroable;
use JsonSerializable;
use Laravel\Nova\AuthorizedToSee;
use Laravel\Nova\Fields\Collapsable;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Makeable;
use Laravel\Nova\WithComponent;
use Stringable;

/**
 * @method static static make(string $name, array $items = [])
 */
class MenuGroup implements JsonSerializable
{
    use AuthorizedToSee;
    use Collapsable;
    use Macroable;
    use Makeable;
    use WithComponent;

    /**
     * The menu's component.
     *
     * @var string
     */
    public $component = 'menu-group';

    /**
     * The menu's items.
     */
    public MenuCollection $items;

    /**
     * Construct a new Menu Group instance.
     */
    public function __construct(
        public Stringable|string $name,
        iterable $items = []
    ) {
        $this->items = new MenuCollection($items);
    }

    /**
     * Get the menu's unique key.
     */
    public function key(): string
    {
        return md5($this->name.$this->items->reduce(static function ($carry, $item) {
            return $carry.'-'.$item->name;
        }, ''));
    }

    /**
     * Prepare the menu for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $request = app(NovaRequest::class);

        return [
            'component' => $this->component,
            'name' => $this->name,
            'items' => $this->items->authorized($request)->withoutEmptyItems()->all(),
            'collapsable' => $this->collapsable,
            'collapsedByDefault' => $this->collapsedByDefault,
            'key' => $this->key(),
        ];
    }
}
