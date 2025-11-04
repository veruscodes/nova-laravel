<?php

namespace Laravel\Nova\Testing\Browser\Components;

use Illuminate\Support\Str;
use Laravel\Dusk\Browser;

class TabIndexComponent extends IndexComponent
{
    /**
     * The tab slug value.
     *
     * @var string
     */
    public $tabSlug;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $resourceName,
        ?string $viaRelationship = null,
        ?string $slug = null
    ) {
        parent::__construct($resourceName, $viaRelationship);

        $this->tabSlug = ! \is_null($slug) ? $slug : (string) Str::of($this->viaRelationship ?? $this->resourceName)->snake()->slug();
    }

    /** {@inheritDoc} */
    #[\Override]
    public function assert(Browser $browser): void
    {
        $browser->pause(500)
            ->whenAvailable("@{$this->tabSlug}-tab-trigger", static function (Browser $browser) {
                $browser->click('');
            });

        parent::assert($browser);
    }
}
