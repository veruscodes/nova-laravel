<?php

namespace Laravel\Nova\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Configuration\ApplicationBuilder;
use Illuminate\Support\Composer;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Util;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\confirm;

#[AsCommand(name: 'nova:install')]
class InstallCommand extends Command
{
    use ResolvesStubPath;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nova:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the Nova resources';

    /**
     * Determine if Nova should be the default authentication routing.
     */
    protected ?bool $isDefaultAuthenticationRouting = null;

    /** {@inheritDoc} */
    #[\Override]
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->isDefaultAuthenticationRouting = (! $this->laravel['router']->has('login') || ! Util::isFortifyRoutesRegisteredForFrontend())
                && confirm('Would you like to use Nova as the default login?', true);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(Filesystem $files)
    {
        $appNamespace = $this->laravel->getNamespace();

        $requiresNovaDevtool = confirm('Install Nova Devtool?', default: true);

        $this->components->task('Publishing Nova Assets / Resources', task: function () {
            $this->callSilent('nova:publish', ['--fortify' => true]);
        });

        $this->components->task('Publishing Nova Service Provider', task: function () {
            $this->callSilent('vendor:publish', ['--tag' => 'nova-provider']);
        });

        $this->components->task('Generating Main Dashboard', task: function () use ($files) {
            $this->callSilent('nova:dashboard', ['name' => 'Main']);
            $files->copy($this->resolveStubPath('/stubs/nova/main-dashboard.stub'), app_path('Nova/Dashboards/Main.php'));
        });

        $this->components->task('Generating Nova\'s Service Provider', task: function () use ($appNamespace, $files) {
            $this->installNovaServiceProvider($files, $appNamespace);
        });

        $this->components->task('Generating User Resource', task: function () use ($files) {
            $this->callSilent('nova:resource', ['name' => 'User']);
            $files->copy($this->resolveStubPath('/stubs/nova/user-resource.stub'), app_path('Nova/User.php'));
        });

        $this->components->task('Configures User Model', task: function () use ($files) {
            $this->configuresUserModel($files);
        });

        $this->components->task('Configures Application Namespace', task: function () use ($appNamespace, $files) {
            $this->configuresAppNamespace($files, $appNamespace);
        });

        if ($requiresNovaDevtool === true) {
            $composer = new Composer($files, $this->laravel->basePath());

            $this->components->task('Install `laravel/nova-devtool` dependency', task: function () use ($composer) {
                $composer->requirePackages(['laravel/nova-devtool'], dev: true);
            });
        }

        $this->components->info('Nova scaffolding installed successfully.');
    }

    /**
     * Install the Nova service providers in the application configuration file.
     *
     * @return void
     */
    protected function installNovaServiceProvider(Filesystem $files, string $appNamespace)
    {
        $appConfig = $files->get(config_path('app.php'), lock: false);

        $eol = Util::eol($appConfig);

        if (class_exists(ApplicationBuilder::class) && $files->exists(base_path('bootstrap/providers.php'))) {
            ServiceProvider::addProviderToBootstrapFile("{$appNamespace}Providers\NovaServiceProvider");

            if ($this->isDefaultAuthenticationRouting === true) {
                $files->replaceInFile(
                    $eol.'            ->withAuthenticationRoutes()'.$eol,
                    $eol.'            ->withAuthenticationRoutes(default: true)'.$eol,
                    app_path('Providers/NovaServiceProvider.php'),
                );
            }

            return;
        }

        if (str_contains($appConfig, "{$appNamespace}Providers\\NovaServiceProvider::class")) {
            return;
        }

        $files->replaceInFile(
            "{$appNamespace}Providers\EventServiceProvider::class,".$eol,
            "{$appNamespace}Providers\EventServiceProvider::class,".$eol."        {$appNamespace}Providers\NovaServiceProvider::class,".$eol,
            config_path('app.php')
        );
    }

    /**
     * Set the proper application namespace on the installed files.
     */
    protected function configuresAppNamespace(Filesystem $files, string $appNamespace): void
    {
        $this->setAppNamespaceOn($files, app_path('Nova/User.php'), $appNamespace);
        $this->setAppNamespaceOn($files, app_path('Providers/NovaServiceProvider.php'), $appNamespace);
    }

    /**
     * Set the proper User's model on the installed files.
     */
    protected function configuresUserModel(Filesystem $files): void
    {
        $namespacedUserModel = Util::userModel();

        if (\is_null($namespacedUserModel) && ! file_exists(app_path('Models/User.php'))) {
            $namespacedUserModel = 'App\User';
        }

        if (! \is_null($namespacedUserModel) && $namespacedUserModel !== 'App\Models\User') {
            $baseUserModel = class_basename($namespacedUserModel);

            $searches = ['$model = \App\Models\User::class', 'class-string<\App\Models\User>'];
            $replacements = ['$model = \\'.$namespacedUserModel.'::class', 'class-string<\\'.$namespacedUserModel.'>'];

            if ($baseUserModel !== 'User') {
                $searches[] = 'use App\Models\User;';
                $replacements[] = 'use '.$namespacedUserModel.' as UserModel;';

                $searches[] = 'function (User $user)';
                $replacements[] = 'function (UserModel $user)';
            } else {
                $searches[] = 'use App\Models\User;';
                $replacements[] = 'use '.$namespacedUserModel.';';
            }

            $files->replaceInFile($searches, $replacements, app_path('Nova/User.php'));
            $files->replaceInFile($searches, $replacements, app_path('Providers/NovaServiceProvider.php'));
        }
    }

    /**
     * Set the namespace on the given file.
     */
    protected function setAppNamespaceOn(Filesystem $files, string $file, string $appNamespace): void
    {
        if ($appNamespace !== 'App\\') {
            $files->replaceInFile('App\\', $appNamespace, $file);
        }
    }
}
