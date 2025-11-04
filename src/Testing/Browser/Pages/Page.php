<?php

namespace Laravel\Nova\Testing\Browser\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page as Dusk;
use Laravel\Nova\Nova;
use Laravel\Nova\Testing\Browser\Concerns\InteractsWithElements;

class Page extends Dusk
{
    use InteractsWithElements;

    /**
     * The Page URL.
     */
    public string $novaPageUrl;

    /**
     * The query strings.
     *
     * @var array<string, mixed>
     */
    public array $queryParams = [];

    /**
     * Create a new page instance.
     */
    public function __construct(string $path = '/')
    {
        $this->setNovaPage($path);
    }

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        if (! empty($this->queryParams)) {
            return $this->novaPageUrl.'?'.http_build_query($this->queryParams);
        }

        return $this->novaPageUrl;
    }

    /**
     * Assert page not found.
     */
    public function assertOk(Browser $browser): void
    {
        $browser->waitForLocation($this->novaPageUrl)
            ->assertPathIs($this->novaPageUrl)
            ->waitFor('@nova-content');
    }

    /**
     * Assert page not found.
     */
    public function assertNotFound(Browser $browser): void
    {
        $browser->on(new NotFound);
    }

    /**
     * Assert page not forbidden.
     */
    public function assertForbidden(Browser $browser): void
    {
        $browser->on(new Forbidden);
    }

    /**
     * Assert page doesn't contain breadcrumb.
     */
    public function assertWithoutBreadcrumb(Browser $browser): void
    {
        $browser->assertMissing('@breadcrumbs');
    }

    /**
     * Set luxon timezone for the frontend.
     */
    public function luxonTimezone(Browser $browser, string $timezone = 'system'): void
    {
        $browser->script('Nova.$testing.timezone("'.$timezone.'")');
    }

    /**
     * Get the global element shortcuts for the site.
     */
    public static function siteElements(): array
    {
        return [
            '@nova-content' => '[dusk="content"]',
            '@nova-form' => '[dusk="content"]',
        ];
    }

    /**
     * Set Nova Page URL.
     *
     * @param  array<string, mixed>  $queryParams
     */
    protected function setNovaPage(string $path, array $queryParams = []): void
    {
        $this->novaPageUrl = Nova::path().'/'.trim($path, '/');
        $this->queryParams = $queryParams;
    }
}
