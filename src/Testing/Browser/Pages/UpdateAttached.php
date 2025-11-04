<?php

namespace Laravel\Nova\Testing\Browser\Pages;

use Illuminate\Database\Eloquent\Model;
use Laravel\Dusk\Browser;

class UpdateAttached extends Page
{
    /**
     * The Resource ID.
     *
     * @var \Illuminate\Database\Eloquent\Model|string|int
     */
    public mixed $resourceId;

    /**
     * The Related ID.
     *
     * @var \Illuminate\Database\Eloquent\Model|string|int
     */
    public mixed $relatedId;

    /**
     * Create a new page instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string|int  $resourceId
     * @param  \Illuminate\Database\Eloquent\Model|string|int  $relatedId
     */
    public function __construct(
        public string $resourceName,
        mixed $resourceId,
        public string $relation,
        mixed $relatedId,
        public ?string $viaRelationship = null,
        public ?string $viaPivotId = null
    ) {
        $this->relatedId = $relatedId instanceof Model ? $relatedId->getKey() : $relatedId;
        $this->resourceId = $resourceId instanceof Model ? $resourceId->getKey() : $resourceId;

        $this->setNovaPage("/resources/{$this->resourceName}/{$this->resourceId}/edit-attached/{$this->relation}/{$this->relatedId}");
    }

    /**
     * Create a new page instance for Belongs-to-Many.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string|int  $resourceId
     * @param  \Illuminate\Database\Eloquent\Model|string|int  $relatedId
     * @return static
     */
    public static function belongsToMany(
        string $resourceName,
        mixed $resourceId,
        string $relation,
        mixed $relatedId,
        ?string $viaRelationship = null,
        ?string $viaPivotId = null
    ) {
        return new static($resourceName, $resourceId, $relation, $relatedId, $viaRelationship, $viaPivotId);
    }

    /**
     * Create a new page instance for Morph-to-Many.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string|int  $resourceId
     * @param  \Illuminate\Database\Eloquent\Model|string|int  $relatedId
     * @return static
     */
    public static function morphToMany(
        string $resourceName,
        mixed $resourceId,
        string $relation,
        mixed $relatedId,
        ?string $viaRelationship = null,
        ?string $viaPivotId = null
    ) {
        return new static($resourceName, $resourceId, $relation, $relatedId, $viaRelationship, $viaPivotId);
    }

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return $this->novaPageUrl.'?'.http_build_query(array_filter([
            'viaRelationship' => $this->viaRelationship ?? $this->relation,
            'viaPivotId' => $this->viaPivotId,
        ]));
    }

    /**
     * Click the update button.
     */
    public function update(Browser $browser): void
    {
        $browser->dismissToasted()
            ->click('@update-button')
            ->pause(750);
    }

    /**
     * Click the update and continue editing button.
     */
    public function updateAndContinueEditing(Browser $browser): void
    {
        $browser->dismissToasted()
            ->click('@update-and-continue-editing-button')
            ->pause(750);
    }

    /**
     * Click the cancel button.
     */
    public function cancel(Browser $browser): void
    {
        $browser->dismissToasted()
            ->click('@cancel-update-attached-button');
    }

    /**
     * Assert that the browser is on the page.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function assert(Browser $browser): void
    {
        $browser->assertOk()->waitFor('@nova-form');
    }
}
