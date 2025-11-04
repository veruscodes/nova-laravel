<?php

namespace Laravel\Nova\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'nova:custom-filter')]
class CustomFilterCommand extends ComponentGeneratorCommand
{
    use RenamesStubs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nova:custom-filter {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new custom filter';

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
            __DIR__.'/filter-stubs',
            $this->componentPath()
        );

        // Filter.js replacements...
        $files->replaceInFile('{{ component }}', $this->componentName(), $this->componentPath().'/resources/js/filter.js');

        // Filter.php replacements...
        $files->replaceInFile('{{ namespace }}', $this->componentNamespace(), $this->componentPath().'/src/Filter.stub');
        $files->replaceInFile('{{ class }}', $this->componentClass(), $this->componentPath().'/src/Filter.stub');
        $files->replaceInFile('{{ component }}', $this->componentName(), $this->componentPath().'/src/Filter.stub');

        $files->move(
            $this->componentPath().'/src/Filter.stub',
            $this->componentPath().'/src/'.$this->componentClass().'.php'
        );

        // FilterServiceProvider.php replacements...
        $files->replaceInFile('{{ namespace }}', $this->componentNamespace(), $this->componentPath().'/src/FilterServiceProvider.stub');
        $files->replaceInFile('{{ component }}', $this->componentName(), $this->componentPath().'/src/FilterServiceProvider.stub');

        // webpack.mix.js replacements...
        $files->replaceInFile('{{ name }}', $this->component(), $this->componentPath().'/webpack.mix.js');

        // Filter composer.json replacements...
        $this->prepareComposerReplacements($files);

        // Rename the stubs with the proper file extensions...
        $this->renameStubs();

        // Register the filter...
        $this->buildComponent('filter');
    }

    /**
     * Get the array of stubs that need PHP file extensions.
     *
     * @return array
     */
    protected function stubsToRename()
    {
        return [
            $this->componentPath().'/src/FilterServiceProvider.stub',
        ];
    }

    /**
     * Get the "title" name of the filter.
     *
     * @return string
     */
    protected function componentTitle()
    {
        return Str::title(str_replace('-', ' ', $this->componentName()));
    }
}
