<?php

namespace Laravel\Nova\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'nova:asset')]
class AssetCommand extends ComponentGeneratorCommand
{
    use RenamesStubs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nova:asset {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new asset';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(Filesystem $files)
    {
        if (! $this->hasValidNameArgument()) {
            return;
        }

        $files->copyDirectory(
            __DIR__.'/asset-stubs',
            $this->componentPath()
        );

        // AssetServiceProvider.php replacements...
        $files->replaceInFile('{{ namespace }}', $this->componentNamespace(), $this->componentPath().'/src/AssetServiceProvider.stub');
        $files->replaceInFile('{{ component }}', $this->componentName(), $this->componentPath().'/src/AssetServiceProvider.stub');
        $files->replaceInFile('{{ name }}', $this->componentName(), $this->componentPath().'/src/AssetServiceProvider.stub');

        // asset.js replacements...
        $files->replaceInFile('{{ class }}', $this->componentClass(), $this->componentPath().'/resources/js/asset.js');
        $files->replaceInFile('{{ name }}', $this->componentName(), $this->componentPath().'/resources/js/asset.js');

        // webpack.mix.js replacements...
        $files->replaceInFile('{{ name }}', $this->component(), $this->componentPath().'/webpack.mix.js');

        // Asset composer.json replacements...
        $this->prepareComposerReplacements($files);

        // Rename the stubs with the proper file extensions...
        $this->renameStubs();

        // Register the asset...
        $this->buildComponent('asset');
    }

    /**
     * Get the array of stubs that need PHP file extensions.
     *
     * @return array
     */
    protected function stubsToRename()
    {
        return [
            $this->componentPath().'/src/AssetServiceProvider.stub',
        ];
    }

    /**
     * Get the "title" name of the asset.
     *
     * @return string
     */
    protected function componentTitle()
    {
        return Str::title(str_replace('-', ' ', $this->componentName()));
    }

    /**
     * Get the component's "snake" name.
     *
     * @return string
     */
    protected function componentSlug()
    {
        return Str::snake($this->componentName(), '-');
    }
}
