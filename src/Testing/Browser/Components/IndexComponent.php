<?php

namespace Laravel\Nova\Testing\Browser\Components;

use Illuminate\Support\Str;
use Laravel\Dusk\Browser;

class IndexComponent extends Component
{
    /**
     * The via Relationship value.
     *
     * @var string
     */
    public $viaRelationship;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $resourceName,
        ?string $viaRelationship = null
    ) {
        if (! \is_null($viaRelationship) && $resourceName !== $viaRelationship) {
            $this->viaRelationship = $viaRelationship;
        }
    }

    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        $selector = '[dusk="'.$this->resourceName.'-index-component"]';

        return \sprintf(
            ! \is_null($this->viaRelationship) ? '%s[data-relationship="%s"]' : '%s', $selector, $this->viaRelationship
        );
    }

    /**
     * Wait for table to be ready.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function waitForTable(Browser $browser, ?int $seconds = null): void
    {
        $browser->waitUntilMissing('@loading-view')
            ->whenAvailable('@resource-table', static function (Browser $browser) use ($seconds) {
                $browser->waitFor('tbody', $seconds);
            }, $seconds);
    }

    /**
     * Wait for empty dialog to be ready.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function waitForEmptyDialog(Browser $browser, ?int $seconds = null): void
    {
        $browser->waitUntilMissing('@loading-view')
            ->waitFor('div[dusk="'.$this->resourceName.'-empty-dialog"]', $seconds);
    }

    /**
     * Search for the given string.
     */
    public function searchFor(Browser $browser, string $search): void
    {
        $browser->type('@search-input', $search)->pause(1000);
    }

    /**
     * Clear the search field.
     */
    public function clearSearch(Browser $browser): void
    {
        $browser->clear('@search-input')->type('@search-input', ' ')->pause(1000);
    }

    /**
     * Click the sortable icon for the given attribute.
     */
    public function sortBy(Browser $browser, string $attribute): void
    {
        $browser->click("@sort-{$attribute}")->waitForTable();
    }

    /**
     * Paginate to the next page of resources.
     */
    public function nextPage(Browser $browser): void
    {
        $browser->click('@next')->waitForTable();
    }

    /**
     * Paginate to the previous page of resources.
     */
    public function previousPage(Browser $browser): void
    {
        $browser->click('@previous')->waitForTable();
    }

    /**
     * Select all the the resources on current page.
     */
    public function selectAllOnCurrentPage(Browser $browser): void
    {
        $browser->within(new SelectAllDropdownComponent, function (Browser $browser) {
            $browser->selectAllOnCurrentPage();
        });
    }

    /**
     * Un-select all the the resources on current page.
     */
    public function unselectAllOnCurrentPage(Browser $browser): void
    {
        $browser->within(new SelectAllDropdownComponent, static function (Browser $browser) {
            $browser->unselectAllOnCurrentPage();
        });
    }

    /**
     * Select all the matching resources.
     */
    public function selectAllMatching(Browser $browser): void
    {
        $browser->within(new SelectAllDropdownComponent, static function (Browser $browser) {
            $browser->selectAllMatching();
        });
    }

    /**
     * Un-select all the matching resources.
     */
    public function unselectAllMatching(Browser $browser): void
    {
        $browser->within(new SelectAllDropdownComponent, static function (Browser $browser) {
            $browser->unselectAllMatching();
        });
    }

    /**
     * Assert on the matching total matching count text.
     */
    public function assertSelectAllMatchingCount(Browser $browser, int $count): void
    {
        $browser->within(new SelectAllDropdownComponent, static function (Browser $browser) use ($count) {
            $browser->assertSelectAllMatchingCount($count);
        });
    }

    /**
     * Set the given filter and filter value for the index.
     *
     * @param  (\callable(\Laravel\Dusk\Browser):(void))|false|null  $postCallback
     */
    public function runFilter(Browser $browser, ?callable $fieldCallback = null, callable|false|null $postCallback = null): void
    {
        $browser->openFilterSelector();

        if (\is_callable($fieldCallback)) {
            $browser->elsewhereWhenAvailable('@filter-menu', function (Browser $browser) use ($fieldCallback) {
                if ($fieldCallback) {
                    \call_user_func($fieldCallback, $browser);
                }
            });
        }

        if ($postCallback !== false) {
            \call_user_func($postCallback ?? static function (Browser $browser) {
                $browser->closeCurrentDropdown();
            }, $browser);
        }
    }

    /**
     * Reset current filter value for the index.
     */
    public function resetFilter(Browser $browser): void
    {
        $this->runFilter($browser, static function (Browser $browser) {
            $browser->press(Str::upper(__('Reset Filters')));
        });
    }

    /**
     * Assert current filter count for the index.
     */
    public function assertFilterCount(Browser $browser, int $count): void
    {
        $browser->within('@filter-selector-button', static function (Browser $browser) use ($count) {
            if ($count <= 0) {
                $browser->assertDontSee($count);
            } else {
                $browser->assertSee($count);
            }
        });
    }

    /**
     * Set the per page value for the index.
     */
    public function setPerPage(Browser $browser, int $value): void
    {
        $this->runFilter($browser, static function (Browser $browser) use ($value) {
            $browser->whenAvailable('select[dusk="per-page-select"]', static function (Browser $browser) use ($value) {
                $browser->select('', $value);
            });
        });
    }

    /**
     * Set the given filter and filter value for the index.
     */
    public function selectFilter(Browser $browser, string $name, mixed $value): void
    {
        $this->runFilter($browser, static function (Browser $browser) use ($name, $value) {
            $browser->whenAvailable('select[dusk="'.Str::slug($name).'-select-filter"]', static function (Browser $browser) use ($value) {
                $browser->select('', $value)->pause(1000);
            });
        });
    }

    /**
     * Indicate that trashed records should not be displayed.
     */
    public function withoutTrashed(Browser $browser): void
    {
        $this->runFilter($browser, static function (Browser $browser) {
            $browser->whenAvailable('[dusk="filter-soft-deletes"]', static function (Browser $browser) {
                $browser->select('select[dusk="trashed-select"]', '')->pause(1000);
            });
        });
    }

    /**
     * Indicate that only trashed records should be displayed.
     */
    public function onlyTrashed(Browser $browser): void
    {
        $this->runFilter($browser, static function (Browser $browser) {
            $browser->whenAvailable('[dusk="filter-soft-deletes"]', static function (Browser $browser) {
                $browser->select('select[dusk="trashed-select"]', 'only')->pause(1000);
            });
        });
    }

    /**
     * Indicate that trashed records should be displayed.
     */
    public function withTrashed(Browser $browser): void
    {
        $this->runFilter($browser, static function (Browser $browser) {
            $browser->whenAvailable('[dusk="filter-soft-deletes"]', static function (Browser $browser) {
                $browser->select('select[dusk="trashed-select"]', 'with');
            });
        });
    }

    /**
     * Open the action selector.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function openActionSelector(Browser $browser): void
    {
        $browser->whenAvailable('@nova-index-action-select', static function (Browser $browser) {
            $browser->click('')->pause(100);
        });
    }

    /**
     * Open the standalone action selector.
     *
     * @return void
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function openStandaloneActionSelector(Browser $browser)
    {
        $browser->whenAvailable('@index-standalone-action-dropdown', static function (Browser $browser) {
            $browser->click('')->pause(100);
        });
    }

    /**
     * Open the filter selector.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function openFilterSelector(Browser $browser): void
    {
        $browser->whenAvailable('@filter-selector', static function (Browser $browser) {
            $browser->click('')->pause(100);
        });
    }

    /**
     * Open the action selector.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function openControlSelectorById(Browser $browser, string|int $id): void
    {
        $browser->whenAvailable("@{$id}-control-selector", static function (Browser $browser) {
            $browser->click('')->pause(300);
        });
    }

    /**
     * assert the action selector is present by ID.
     *
     * @param  int|string  $id
     * @return void
     */
    public function assertPresentControlSelectorById(Browser $browser, $id)
    {
        $browser->assertPresent("@{$id}-control-selector");
    }

    /**
     * assert the action selector is missing by ID.
     *
     * @param  int|string  $id
     * @return void
     */
    public function assertMissingControlSelectorById(Browser $browser, $id)
    {
        $browser->assertMissing("@{$id}-control-selector");
    }

    /**
     * Select the action with the given URI key.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function selectAction(Browser $browser, string $uriKey, callable $fieldCallback): void
    {
        $browser->whenAvailable('@nova-index-action-select', static function (Browser $browser) use ($uriKey) {
            $browser->select('', $uriKey)
                ->pause(100)
                ->assertSelected('', '');
        });

        $browser->elsewhereWhenAvailable(new Modals\ConfirmActionModalComponent, static function (Browser $browser) use ($fieldCallback) {
            $fieldCallback($browser);
        });
    }

    /**
     * Select the standalone action with the given URI key.
     *
     * @return void
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function selectStandaloneAction(Browser $browser, string $uriKey, callable $fieldCallback)
    {
        $browser->whenAvailable('@index-standalone-action-dropdown', static function (Browser $browser) {
            $browser->click('');
        })->elseWhereWhenAvailable('div[data-menu-open="true"]', static function (Browser $browser) use ($uriKey) {
            $browser->click("button[data-action-id='{$uriKey}']");
        });

        $browser->elsewhereWhenAvailable(new Modals\ConfirmActionModalComponent, static function (Browser $browser) use ($fieldCallback) {
            \call_user_func($fieldCallback, $browser);
        });
    }

    /**
     * Run the action with the given URI key.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function runAction(Browser $browser, string $uriKey, ?callable $fieldCallback = null): void
    {
        $this->selectAction($browser, $uriKey, static function (Browser $browser) use ($fieldCallback) {
            if ($fieldCallback) {
                \call_user_func($fieldCallback, $browser);
            }

            $browser->waitForText('Run Action')->click('@confirm-action-button')->pause(250);
        });
    }

    /**
     * Run the standalone action with the given URI key.
     *
     * @return void
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function runStandaloneAction(Browser $browser, string $uriKey, ?callable $fieldCallback = null)
    {
        $this->selectStandaloneAction($browser, $uriKey, static function (Browser $browser) use ($fieldCallback) {
            $browser->pause(2000);

            if ($fieldCallback) {
                \call_user_func($fieldCallback, $browser);
            }

            $browser->waitForText('Run Action')->click('@confirm-action-button')->pause(250);
        });
    }

    /**
     * Select the action with the given URI key.
     */
    public function selectInlineAction(Browser $browser, string|int $id, string $uriKey, callable $fieldCallback): void
    {
        $browser->openControlSelectorById($id)
            ->elseWhereWhenAvailable('div[data-menu-open="true"]', static function (Browser $browser) use ($uriKey) {
                $browser->click("button[data-action-id='{$uriKey}']");
            })->pause(500);

        $browser->elsewhereWhenAvailable(new Modals\ConfirmActionModalComponent, static function (Browser $browser) use ($fieldCallback) {
            \call_user_func($fieldCallback, $browser);
        });
    }

    /**
     * Run the action with the given URI key.
     */
    public function runInlineAction(Browser $browser, string|int $id, string $uriKey, ?callable $fieldCallback = null): void
    {
        $this->selectInlineAction($browser, $id, $uriKey, static function (Browser $browser) use ($fieldCallback) {
            if ($fieldCallback) {
                \call_user_func($fieldCallback, $browser);
            }

            $browser->click('@confirm-action-button')->pause(250);
        });
    }

    /**
     * Check the user at the given resource table row index.
     *
     * @param  int|string  $id
     * @param  int|string|null  $pivotId
     * @return void
     */
    public function clickCheckboxForId(Browser $browser, $id, $pivotId = null)
    {
        if (! \is_null($pivotId)) {
            $browser->click('[data-pivot-id="'.$pivotId.'"][dusk="'.$id.'-row"] [role="checkbox"]');
        } else {
            $browser->click('[dusk="'.$id.'-checkbox"]');
        }

        $browser->pause(175);
    }

    /**
     * Replicate the given resource table row index.
     */
    public function replicateResourceById(Browser $browser, string|int $id): void
    {
        $browser->openControlSelectorById($id)
            ->elsewhereWhenAvailable("@{$id}-replicate-button", static function (Browser $browser) {
                $browser->click('');
            })->pause(500);
    }

    /**
     * Preview the given resource table row index.
     */
    public function previewResourceById(Browser $browser, string|int $id): void
    {
        $browser->openControlSelectorById($id)
            ->elsewhereWhenAvailable("@{$id}-preview-button", static function (Browser $browser) {
                $browser->click('');
            })->pause(500);
    }

    /**
     * Delete the user at the given resource table row index.
     */
    public function deleteResourceById(Browser $browser, string|int $id): void
    {
        $browser->click("@{$id}-delete-button")
            ->elsewhereWhenAvailable(new Modals\DeleteResourceModalComponent, static function (Browser $browser) {
                $browser->confirm();
            })->pause(500);
    }

    /**
     * Restore the user at the given resource table row index.
     */
    public function restoreResourceById(Browser $browser, string|int $id): void
    {
        $browser->click("@{$id}-restore-button")
            ->elsewhereWhenAvailable(new Modals\RestoreResourceModalComponent, static function (Browser $browser) {
                $browser->confirm();
            })->pause(500);
    }

    /**
     * View the user at the given resource table row index.
     *
     * @param  int|string  $id
     * @return void
     */
    public function viewResourceById(Browser $browser, $id)
    {
        $browser->click("@{$id}-view-button")->pause(500);
    }

    /**
     * Edit the user at the given resource table row index.
     *
     * @param  int|string  $id
     * @return void
     */
    public function editResourceById(Browser $browser, $id)
    {
        $browser->click("@{$id}-edit-button")->pause(500);
    }

    /**
     * Delete the resources selected via checkboxes.
     */
    public function deleteSelected(Browser $browser): void
    {
        $browser->click('@delete-menu')
            ->pause(300)
            ->elsewhere('', static function (Browser $browser) {
                $browser->click('[dusk="delete-selected-button"]')
                    ->elsewhereWhenAvailable(new Modals\DeleteResourceModalComponent, static function (Browser $browser) {
                        $browser->confirm();
                    });
            })->pause(1000);
    }

    /**
     * Restore the resources selected via checkboxes.
     */
    public function restoreSelected(Browser $browser): void
    {
        $browser->click('@delete-menu')
            ->pause(300)
            ->elsewhere('', static function (Browser $browser) {
                $browser->click('[dusk="restore-selected-button"]')
                    ->elsewhereWhenAvailable(new Modals\RestoreResourceModalComponent, static function (Browser $browser) {
                        $browser->confirm();
                    });
            })->pause(1000);
    }

    /**
     * Restore the resources selected via checkboxes.
     */
    public function forceDeleteSelected(Browser $browser): void
    {
        $browser->click('@delete-menu')
            ->pause(300)
            ->elsewhere('', static function (Browser $browser) {
                $browser->click('[dusk="force-delete-selected-button"]')
                    ->elsewhereWhenAvailable(new Modals\DeleteResourceModalComponent, static function (Browser $browser) {
                        $browser->confirm();
                    });
            })->pause(1000);
    }

    /**
     * Assert that the browser page contains the component.
     *
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function assert(Browser $browser): void
    {
        $browser->pause(500);

        tap($this->selector(), static function (string $selector) use ($browser) {
            $browser->waitFor($selector)
                ->assertVisible($selector)
                ->scrollIntoView($selector);
        });
    }

    /**
     * Assert that the given resource is visible.
     */
    public function assertSeeResource(Browser $browser, string|int $id, string|int|null $pivotId = null): void
    {
        if (! \is_null($pivotId)) {
            $browser->assertVisible('[dusk="'.$id.'-row"][data-pivot-id="'.$pivotId.'"]');
        } else {
            $browser->assertVisible("@{$id}-row");
        }
    }

    /**
     * Assert that the given resource is not visible.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string|int  $id
     * @param  \Illuminate\Database\Eloquent\Model|string|int|null  $pivotId
     */
    public function assertDontSeeResource(Browser $browser, mixed $id, mixed $pivotId = null): void
    {
        if (! \is_null($pivotId)) {
            $browser->assertMissing('[dusk="'.$id.'-row"][data-pivot-id="'.$pivotId.'"]');
        } else {
            $browser->assertMissing("@{$id}-row");
        }
    }

    /**
     * Assert that the checkbox is checked.
     */
    public function assertCheckboxChecked(Browser $browser, string $selector): void
    {
        $browser->assertAttribute($selector, 'data-state', 'checked');
    }

    /**
     * Assert that the checkbox is not checked.
     */
    public function assertCheckboxNotChecked(Browser $browser, string $selector): void
    {
        $browser->assertAttribute($selector, 'data-state', 'unchecked');
    }

    /**
     * Get the element shortcuts for the component.
     */
    public function elements(): array
    {
        return [
            '@nova-index-action-select' => 'select[dusk="action-select"]',
            '@nova-opened-modal' => '.modal[data-modal-open=true]',
        ];
    }
}
