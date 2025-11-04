<?php

namespace Laravel\Nova\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Laravel\Nova\Nova;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

use function Illuminate\Filesystem\join_paths;

#[AsCommand(name: 'nova:publish')]
class PublishCommand extends Command
{
    /**
     * The name  of the console command.
     *
     * @var string
     */
    protected $name = 'nova:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all of the Nova resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(Filesystem $files)
    {
        if (
            $this->option('fortify') === true
            && Nova::fortify()->canManageTwoFactorAuthentication()
            && ! $this->migrationNameExists($files, 'add_two_factor_columns_to_users_table')
        ) {
            $this->call('vendor:publish', ['--tag' => 'fortify-migrations']);
        }

        $this->call('vendor:publish', [
            '--tag' => 'nova-config',
            '--force' => $this->option('force'),
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'nova-assets',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'nova-lang',
            '--force' => $this->option('force'),
        ]);

        $this->call('view:clear');
    }

    /**
     * Determine whether a migration for the table already exists.
     */
    protected function migrationNameExists(Filesystem $files, string $name): bool
    {
        return \count($files->glob(
            join_paths($this->laravel->databasePath('migrations'), '*_*_*_*_'.$name.'.php')
        )) !== 0;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Overwrite any existing files'],
            ['fortify', null, InputOption::VALUE_NEGATABLE, 'Publish Laravel Fortify features'],
        ];
    }
}
