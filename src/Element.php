<?php

namespace Laravel\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;
use JsonSerializable;

/**
 * @method static static make(string|null $component = null)
 */
abstract class Element implements JsonSerializable
{
    use AuthorizedToSee;
    use Macroable;
    use Makeable;
    use Metable;
    use ProxiesCanSeeToGate;
    use WithComponent;

    /**
     * The element's component.
     *
     * @var string
     */
    public $component;

    /**
     * Indicates if the element is only shown on the detail screen.
     *
     * @var bool
     */
    public $onlyOnDetail = false;

    /**
     * Create a new element.
     */
    public function __construct(?string $component = null)
    {
        $this->component = $component ?? $this->component;
    }

    /**
     * Determine if the element should be displayed for the given request.
     *
     * @return bool
     */
    public function authorize(Request $request)
    {
        return $this->authorizedToSee($request);
    }

    /**
     * Specify that the element should only be shown on the detail view.
     *
     * @return $this
     */
    public function onlyOnDetail()
    {
        $this->onlyOnDetail = true;

        return $this;
    }

    /**
     * Prepare the element for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_merge([
            'component' => $this->component(),
            'prefixComponent' => false,
            'onlyOnDetail' => $this->onlyOnDetail,
        ], $this->meta());
    }
}
