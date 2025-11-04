<?php

namespace Laravel\Nova\Testing\Browser\Pages;

use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Components\Controls\RelationSelectControlComponent;
use Laravel\Nova\Testing\Browser\Concerns\InteractsWithInlineCreateRelation;

trait InteractsWithRelations
{
    use HasSearchable;
    use InteractsWithInlineCreateRelation;

    /**
     * Select for the given value for a relationship attribute.
     */
    public function selectRelation(Browser $browser, string $attribute, ?string $value = null): void
    {
        $browser->whenAvailable(new RelationSelectControlComponent($attribute), static function (Browser $browser) use ($value) {
            $browser->assertSelectHasOption('', $value)->select('', $value);
        });
    }

    /**
     * Search for the given value for a searchable relationship attribute.
     */
    public function searchRelation(Browser $browser, string $attribute, string $search): void
    {
        $this->searchInput($browser, $attribute, $search);
    }

    /**
     * Reset the searchable relationship attribute.
     */
    public function resetSearchRelation(Browser $browser, string $attribute): void
    {
        $this->resetSearchResult($browser, $attribute);
    }

    /**
     * Select the currently highlighted searchable relation.
     */
    public function firstSearchableResult(Browser $browser, string $attribute): void
    {
        $this->selectFirstSearchResult($browser, $attribute);
    }

    /**
     * Search and select the currently highlighted searchable relation.
     */
    public function searchFirstRelation(Browser $browser, string $attribute, string $search): void
    {
        $this->searchAndSelectFirstResult($browser, $attribute, $search);
    }

    /**
     * Indicate that trashed relations should be included in the search results.
     */
    public function waitForTrashedRelation(Browser $browser, string $resourceName): void
    {
        $browser->waitFor("@{$resourceName}-with-trashed-checkbox", 5);
    }

    /**
     * Indicate that trashed relations should be included in the search results.
     */
    public function withTrashedRelation(Browser $browser, string $resourceName): void
    {
        $browser->waitForTrashedRelation($resourceName)->click('')->with(
            "@{$resourceName}-with-trashed-checkbox",
            static function (Browser $browser) {
                $browser->waitFor('input[type="checkbox"]')
                    ->check('input[type="checkbox"]')
                    ->pause(250);
            }
        );
    }

    /**
     * Indicate that trashed relations should not be included in the search results.
     */
    public function withoutTrashedRelation(Browser $browser, string $resourceName): void
    {
        $browser->waitForTrashedRelation($resourceName)
            ->uncheck('[dusk="'.$resourceName.'-with-trashed-checkbox"] input[type="checkbox"]')
            ->pause(250);
    }
}
