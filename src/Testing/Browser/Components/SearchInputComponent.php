<?php

namespace Laravel\Nova\Testing\Browser\Components;

use Facebook\WebDriver\WebDriverKeys;
use Illuminate\Support\Arr;
use Laravel\Dusk\Browser;
use Laravel\Dusk\ElementResolver;

class SearchInputComponent extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $attribute
    ) {
        //
    }

    /**
     * Show the component dropdown.
     */
    public function showSearchDropdown(Browser $browser): void
    {
        $resolver = new ElementResolver($browser->driver, 'body');

        $input = $resolver->find("[dusk='{$this->attribute}-search-input-dropdown']");

        if (\is_null($input) || ! $input->isDisplayed()) {
            $browser->click('');
        }
    }

    /**
     * Search for the given value for a searchable field attribute.
     */
    public function searchInput(Browser $browser, string $search, int $pause = 500): void
    {
        $this->showSearchDropdown($browser);

        $browser->elsewhereWhenAvailable("{$this->selector()}-dropdown", static function (Browser $browser) use ($search) {
            $browser->type('input[type="search"]', $search);
        });

        $browser->pause($pause);
    }

    /**
     * Reset the searchable field.
     */
    public function resetSearchResult(Browser $browser): void
    {
        $this->cancelSelectingSearchResult($browser);

        $selector = "{$this->selector()}-clear-button";

        $element = $browser->element($selector);

        if (! \is_null($element) && $element->isDisplayed()) {
            $browser->click($selector)->pause(1500);
        }
    }

    /**
     * Search and select the searchable field by result index.
     */
    public function searchAndSelectResult(Browser $browser, string $search, int $resultIndex): void
    {
        $this->searchInput($browser, $search, 1500);

        $this->selectSearchResult($browser, $resultIndex);
    }

    /**
     * Select the searchable field by result index.
     */
    public function selectSearchResult(Browser $browser, int $resultIndex): void
    {
        $selector = $this->selector();

        $browser->elseWhereWhenAvailable("{$selector}-dropdown", static function (Browser $browser) use ($selector, $resultIndex) {
            $browser->whenAvailable("{$selector}-result-{$resultIndex}", static function (Browser $browser) {
                $browser->click('')->pause(300);
            });
        });
    }

    /**
     * Select the currently highlighted searchable field.
     */
    public function cancelSelectingSearchResult(Browser $browser): void
    {
        $browser->driver->getKeyboard()->sendKeys(WebDriverKeys::ESCAPE);

        $browser->pause(150);
    }

    /**
     * Select the currently highlighted searchable field.
     */
    public function selectFirstSearchResult(Browser $browser): void
    {
        $this->selectSearchResult($browser, 0);
    }

    /**
     * Search and select the currently highlighted searchable relation.
     */
    public function searchFirstRelation(Browser $browser, string $search): void
    {
        $this->searchAndSelectFirstResult($browser, $search);
    }

    /**
     * Search and select the currently highlighted searchable field.
     */
    public function searchAndSelectFirstResult(Browser $browser, string $search): void
    {
        $this->searchAndSelectResult($browser, $search, 0);
    }

    /**
     * Assert on searchable results.
     *
     * @param  callable(\Laravel\Nova\Browser, string):void  $fieldCallback
     * @return void
     */
    public function assertSearchResult(Browser $browser, callable $fieldCallback)
    {
        $this->showSearchDropdown($browser);

        $selector = $this->selector();

        $browser->elsewhereWhenAvailable("{$selector}-dropdown", function (Browser $browser) use ($selector, $fieldCallback) {
            \call_user_func($fieldCallback, $browser, $selector);

            $this->cancelSelectingSearchResult($browser);
        });
    }

    /**
     * Assert on searchable results is locked to single result.
     */
    public function assertSelectedSearchResult(Browser $browser, string $search): void
    {
        $browser->assertSeeIn("{$this->selector()}-selected", $search);
    }

    /**
     * Assert on searchable results is locked to single result.
     */
    public function assertSelectedFirstSearchResult(Browser $browser, string $search): void
    {
        $this->assertSelectedSearchResult($browser, $search);

        $this->assertSearchResult($browser, static function (Browser $browser, $attribute) use ($search) {
            $browser->assertSeeIn("{$attribute}-result-0", $search)
                ->assertNotPresent("{$attribute}-result-1")
                ->assertNotPresent("{$attribute}-result-2")
                ->assertNotPresent("{$attribute}-result-3")
                ->assertNotPresent("{$attribute}-result-4");
        });
    }

    /**
     * Assert on searchable results is empty.
     */
    public function assertEmptySearchResult(Browser $browser): void
    {
        $this->assertSearchResult($browser, static function (Browser $browser, $attribute) {
            $browser->assertNotPresent("{$attribute}-result-0")
                ->assertNotPresent("{$attribute}-result-1")
                ->assertNotPresent("{$attribute}-result-2")
                ->assertNotPresent("{$attribute}-result-3")
                ->assertNotPresent("{$attribute}-result-4");
        });
    }

    /**
     * Assert on searchable results has the search value.
     */
    public function assertSearchResultContains(Browser $browser, string|array $search): void
    {
        $this->assertSearchResult($browser, static function (Browser $browser, $attribute) use ($search) {
            foreach (Arr::wrap($search) as $keyword) {
                $browser->assertSeeIn("{$attribute}-results", $keyword);
            }
        });
    }

    /**
     * Assert on searchable results doesn't has the search value.
     *
     * @param  string|array  $search
     */
    public function assertSearchResultDoesNotContains(Browser $browser, string $search): void
    {
        $this->assertSearchResult($browser, static function (Browser $browser, $attribute) use ($search) {
            foreach (Arr::wrap($search) as $keyword) {
                $browser->assertDontSeeIn("{$attribute}-results", $keyword);
            }
        });
    }

    /** {@inheritDoc} */
    #[\Override]
    public function assert(Browser $browser)
    {
        $browser->waitFor($this->selector());
    }

    /** {@inheritDoc} */
    public function selector(): string
    {
        return "@{$this->attribute}-search-input";
    }
}
