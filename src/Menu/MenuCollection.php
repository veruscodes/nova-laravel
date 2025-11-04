<?php

namespace Laravel\Nova\Menu;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use JsonSerializable;

/**
 * @template TKey of int
 * @template TValue of \Laravel\Nova\Menu\MenuGroup|\Laravel\Nova\Menu\MenuItem|\Laravel\Nova\Menu\MenuList|non-empty-array
 *
 * @extends \Illuminate\Support\Collection<TKey, TValue>
 */
class MenuCollection extends Collection
{
    /**
     * Filter menus should be displayed for the given request.
     *
     * @return static<int, TValue>
     */
    public function authorized(Request $request)
    {
        /** @phpstan-ignore return.type */
        return $this->reject(
            static fn ($menu) => method_exists($menu, 'authorizedToSee') && ! $menu->authorizedToSee($request)
        )->values();
    }

    /**
     * Resolves menus and remove empty group or lists.
     *
     * @return static<int, TValue>
     */
    public function withoutEmptyItems()
    {
        /** @phpstan-ignore return.type */
        return $this->transform(static function ($menu) {
            if ($menu instanceof JsonSerializable) {
                $payload = $menu->jsonSerialize();

                if (($menu instanceof MenuGroup || $menu instanceof MenuList) && \count($payload['items']) === 0) {
                    return null;
                }

                return $payload;
            }

            return $menu;
        })->filter()->values();
    }
}
