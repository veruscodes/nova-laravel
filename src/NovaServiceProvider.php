<?php

namespace Laravel\Nova;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\ServiceProvider;

class NovaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }

        $this->registerResources();
        $this->registerRelationsMacros();
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/Console/stubs/NovaServiceProvider.stub' => app_path('Providers/NovaServiceProvider.php'),
        ], 'nova-provider');

        $this->publishes([
            __DIR__.'/../config/nova.php' => config_path('nova.php'),
        ], 'nova-config');

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/nova'),
        ], ['nova-assets', 'laravel-assets']);

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/nova'),
        ], 'nova-lang');

        if (method_exists($this, 'publishesMigrations')) {
            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], Nova::runsMigrations() ? 'nova-migrations' : null);
        } else {
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'nova-migrations');
        }
    }

    /**
     * Register the package resources such as routes, templates, etc.
     */
    protected function registerResources(): void
    {
        // ...
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->commands([
            Console\ActionCommand::class,
            Console\AssetCommand::class,
            Console\BaseResourceCommand::class,
            Console\CardCommand::class,
            Console\CustomFilterCommand::class,
            Console\DashboardCommand::class,
            Console\FilterCommand::class,
            Console\FieldCommand::class,
            Console\InstallCommand::class,
            Console\LensCommand::class,
            Console\CheckLicenseCommand::class,
            Console\PartitionCommand::class,
            Console\PolicyMakeCommand::class,
            Console\ProgressCommand::class,
            Console\PublishCommand::class,
            Console\RepeatableCommand::class,
            Console\ResourceCommand::class,
            Console\ResourceToolCommand::class,
            Console\StubPublishCommand::class,
            Console\TableCommand::class,
            Console\TranslateCommand::class,
            Console\ToolCommand::class,
            Console\TrendCommand::class,
            Console\UserCommand::class,
            Console\ValueCommand::class,
        ]);
    }

    /**
     * Register Relations macros.
     */
    protected function registerRelationsMacros(): void
    {
        BelongsToMany::mixin(new Query\Mixin\BelongsToMany);
    }
}
