<?php

namespace Laravel\Nova\Testing\Browser\Pages;

use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Components\SearchInputComponent;

trait HasSearchable
{
    /**
     * Search for the given value for a searchable field attribute.
     */
    public function searchInput(Browser $browser, string $attribute, string $search, int $pause = 500): void
    {
        $browser->whenAvailable(new SearchInputComponent($attribute), static function (Browser $browser) use ($search, $pause) {
            $browser->searchInput($search, $pause);
        });
    }

    /**
     * Reset the searchable field.
     */
    public function resetSearchResult(Browser $browser, string $attribute): void
    {
        $browser->whenAvailable(new SearchInputComponent($attribute), static function (Browser $browser) {
            $browser->resetSearchResult();
        });
    }

    /**
     * Select the searchable field by result index.
     */
    public function selectSearchResult(Browser $browser, string $attribute, int $resultIndex): void
    {
        $browser->whenAvailable(new SearchInputComponent($attribute), static function (Browser $browser) use ($resultIndex) {
            $browser->selectSearchResult($resultIndex);
        });
    }

    /**
     * Select the currently highlighted searchable field.
     */
    public function selectFirstSearchResult(Browser $browser, string $attribute): void
    {
        $this->selectSearchResult($browser, $attribute, 0);
    }

    /**
     * Select the currently highlighted searchable field.
     */
    public function cancelSelectingSearchResult(Browser $browser, string $attribute): void
    {
        $browser->whenAvailable(new SearchInputComponent($attribute), static function (Browser $browser) {
            $browser->cancelSelectingSearchResult();
        });
    }

    /**
     * Search and select the searchable field by result index.
     */
    public function searchAndSelectResult(Browser $browser, string $attribute, string $search, int $resultIndex): void
    {
        $browser->whenAvailable(new SearchInputComponent($attribute), static function (Browser $browser) use ($search, $resultIndex) {
            $browser->searchAndSelectResult($search, $resultIndex);
        });
    }

    /**
     * Search and select the currently highlighted searchable field.
     */
    public function searchAndSelectFirstResult(Browser $browser, string $attribute, string $search): void
    {
        $this->searchAndSelectResult($browser, $attribute, $search, 0);
    }

    /**
     * Assert on searchable results.
     *
     * @param  callable(\Laravel\Nova\Browser, string):void  $fieldCallback
     */
    public function assertSearchResult(Browser $browser, string $attribute, callable $fieldCallback): void
    {
        $browser->whenAvailable(new SearchInputComponent($attribute), static function (Browser $browser) use ($fieldCallback) {
            $browser->assertSearchResult($fieldCallback);
        });
    }

    /**
     * Assert on searchable results current value.
     */
    public function assertSelectedSearchResult(Browser $browser, string $attribute, string $search): void
    {
        $browser->whenAvailable(new SearchInputComponent($attribute), static function (Browser $browser) use ($search) {
            $browser->assertSelectedSearchResult($search);
        });
    }

    /**
     * Assert on searchable results is locked to single result.
     */
    public function assertSelectedFirstSearchResult(Browser $browser, string $attribute, string $search): void
    {
        $browser->whenAvailable(new SearchInputComponent($attribute), static function (Browser $browser) use ($search) {
            $browser->assertSelectedFirstSearchResult($search);
        });
    }

    /**
     * Assert on searchable results is empty.
     */
    public function assertEmptySearchResult(Browser $browser, string $attribute): void
    {
        $browser->whenAvailable(new SearchInputComponent($attribute), static function (Browser $browser) {
            $browser->assertEmptySearchResult();
        });
    }

    /**
     * Assert on searchable results has the search value.
     */
    public function assertSearchResultContains(Browser $browser, string $attribute, string|array $search): void
    {
        $browser->whenAvailable(new SearchInputComponent($attribute), static function (Browser $browser) use ($search) {
            $browser->assertSearchResultContains($search);
        });
    }

    /**
     * Assert on searchable results doesn't has the search value.
     */
    public function assertSearchResultDoesNotContains(Browser $browser, string $attribute, string|array $search): void
    {
        $browser->whenAvailable(new SearchInputComponent($attribute), static function (Browser $browser) use ($search) {
            $browser->assertSearchResultDoesNotContains($search);
        });
    }
}
