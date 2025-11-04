<?php

namespace Laravel\Nova\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'nova:resource-tool')]
class ResourceToolCommand extends ComponentGeneratorCommand
{
    use RenamesStubs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nova:resource-tool {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new resource tool';

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
            __DIR__.'/resource-tool-stubs',
            $this->componentPath()
        );

        // Tool.js replacements...
        $files->replaceInFile('{{ component }}', $this->componentName(), $this->componentPath().'/resources/js/tool.js');

        // Tool.vue replacements...
        $files->replaceInFile('{{ title }}', $this->componentTitle(), $this->componentPath().'/resources/js/components/Tool.vue');

        // Tool.php replacements...
        $files->replaceInFile('{{ namespace }}', $this->componentNamespace(), $this->componentPath().'/src/Tool.stub');
        $files->replaceInFile('{{ class }}', $this->componentClass(), $this->componentPath().'/src/Tool.stub');
        $files->replaceInFile('{{ component }}', $this->componentName(), $this->componentPath().'/src/Tool.stub');
        $files->replaceInFile('{{ title }}', $this->componentTitle(), $this->componentPath().'/src/Tool.stub');

        $files->move(
            $this->componentPath().'/src/Tool.stub',
            $this->componentPath().'/src/'.$this->componentClass().'.php'
        );

        // ToolServiceProvider.php replacements...
        $files->replaceInFile('{{ namespace }}', $this->componentNamespace(), $this->componentPath().'/src/ToolServiceProvider.stub');
        $files->replaceInFile('{{ component }}', $this->componentName(), $this->componentPath().'/src/ToolServiceProvider.stub');
        $files->replaceInFile('{{ name }}', $this->componentName(), $this->componentPath().'/src/ToolServiceProvider.stub');

        // webpack.mix.js replacements...
        $files->replaceInFile('{{ name }}', $this->component(), $this->componentPath().'/webpack.mix.js');

        // Tool composer.json replacements...
        $this->prepareComposerReplacements($files);

        // Rename the stubs with the proper file extensions...
        $this->renameStubs();

        // Register the tool...
        $this->buildComponent('resource-tool');
    }

    /**
     * Get the array of stubs that need PHP file extensions.
     *
     * @return array
     */
    protected function stubsToRename()
    {
        return [
            $this->componentPath().'/src/ToolServiceProvider.stub',
            $this->componentPath().'/routes/api.stub',
        ];
    }

    /**
     * Get the "title" name of the tool.
     *
     * @return string
     */
    protected function componentTitle()
    {
        return Str::title(str_replace('-', ' ', $this->componentName()));
    }
}
