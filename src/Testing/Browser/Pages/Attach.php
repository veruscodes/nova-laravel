<?php

namespace Laravel\Nova\Testing\Browser\Pages;

use Illuminate\Database\Eloquent\Model;
use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Components\SearchInputComponent;

class Attach extends Page
{
    use InteractsWithRelations;

    /**
     * The Resource ID.
     *
     * @var \Illuminate\Database\Eloquent\Model|string|int
     */
    public mixed $resourceId;

    /**
     * Create a new page instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string|int  $resourceId
     */
    public function __construct(
        public string $resourceName,
        mixed $resourceId,
        public string $relation,
        public ?string $viaRelationship = null,
        public bool $polymorphic = false
    ) {
        $this->resourceId = $resourceId instanceof Model ? $resourceId->getKey() : $resourceId;

        $this->setNovaPage("/resources/{$this->resourceName}/{$this->resourceId}/attach/{$this->relation}");
    }

    /**
     * Create a new page instance for Belongs-to-Many.
     *
     * @param  string  $resourceId
     * @return static
     */
    public static function belongsToMany(
        string $resourceName,
        mixed $resourceId,
        string $relation,
        ?string $viaRelationship = null
    ) {
        return new static($resourceName, $resourceId, $relation, $viaRelationship);
    }

    /**
     * Create a new page instance for Morph-to-Many.
     *
     * @param  string  $resourceId
     * @return static
     */
    public static function morphToMany(
        string $resourceName,
        mixed $resourceId,
        string $relation,
        ?string $viaRelationship = null
    ) {
        return new static($resourceName, $resourceId, $relation, $viaRelationship, true);
    }

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return $this->novaPageUrl.'?'.http_build_query([
            'viaRelationship' => $this->viaRelationship ?? $this->relation,
            'polymorphic' => $this->polymorphic === true ? 1 : 0,
        ]);
    }

    /**
     * Select the attachable resource with the given ID.
     */
    public function selectAttachable(Browser $browser, string|int $id): void
    {
        $this->selectRelation($browser, 'attachable', (string) $id);
    }

    /**
     * Select the attachable resource with the given ID.
     */
    public function searchAttachable(Browser $browser, string|int $id): void
    {
        $browser->within(new SearchInputComponent($this->relation), static function (Browser $browser) use ($id) {
            $browser->searchFirstRelation((string) $id);
        });
    }

    /**
     * Click the attach button.
     */
    public function create(Browser $browser): void
    {
        $browser->dismissToasted()
            ->click('@attach-button')
            ->pause(1000);
    }

    /**
     * Click the update and continue editing button.
     */
    public function createAndAttachAnother(Browser $browser): void
    {
        $browser->dismissToasted()
            ->click('@attach-and-attach-another-button')
            ->pause(750);
    }

    /**
     * Click the cancel button.
     */
    public function cancel(Browser $browser): void
    {
        $browser->dismissToasted()
            ->click('@cancel-attach-button');
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertOk()->waitFor('@nova-form');
    }
}
