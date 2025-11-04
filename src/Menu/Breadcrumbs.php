<?php

namespace Laravel\Nova\Menu;

use JsonSerializable;
use Laravel\Nova\AuthorizedToSee;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Makeable;

class Breadcrumbs implements JsonSerializable
{
    use AuthorizedToSee;
    use Makeable;

    /**
     * Construct a new Breadcrumb instance.
     */
    public function __construct(
        public ?iterable $items = null
    ) {
        //
    }

    /**
     * Prepare the menu for JSON serialization.
     *
     * @return array{name: string, path: string|null}|array
     */
    public function jsonSerialize(): array
    {
        return $this->authorizedToSee(app(NovaRequest::class))
            ? $this->items
            : [];
    }
}
