<?php

namespace Laravel\Nova\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'nova:card')]
class CardCommand extends ComponentGeneratorCommand
{
    use RenamesStubs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nova:card {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new card';

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
            __DIR__.'/card-stubs',
            $this->componentPath()
        );

        // Card.js replacements...
        $files->replaceInFile('{{ title }}', $this->componentTitle(), $this->componentPath().'/resources/js/components/Card.vue');
        $files->replaceInFile('{{ component }}', $this->componentName(), $this->componentPath().'/resources/js/card.js');

        // Card.php replacements...
        $files->replaceInFile('{{ namespace }}', $this->componentNamespace(), $this->componentPath().'/src/Card.stub');
        $files->replaceInFile('{{ class }}', $this->componentClass(), $this->componentPath().'/src/Card.stub');
        $files->replaceInFile('{{ component }}', $this->componentName(), $this->componentPath().'/src/Card.stub');

        $files->move(
            $this->componentPath().'/src/Card.stub',
            $this->componentPath().'/src/'.$this->componentClass().'.php'
        );

        // CardServiceProvider.php replacements...
        $files->replaceInFile('{{ namespace }}', $this->componentNamespace(), $this->componentPath().'/src/CardServiceProvider.stub');
        $files->replaceInFile('{{ component }}', $this->componentName(), $this->componentPath().'/src/CardServiceProvider.stub');
        $files->replaceInFile('{{ name }}', $this->componentName(), $this->componentPath().'/src/CardServiceProvider.stub');

        // webpack.mix.js replacements...
        $files->replaceInFile('{{ name }}', $this->component(), $this->componentPath().'/webpack.mix.js');

        // Card composer.json replacements...
        $this->prepareComposerReplacements($files);

        // Rename the stubs with the proper file extensions...
        $this->renameStubs();

        // Register the card...
        $this->buildComponent('card');
    }

    /**
     * Get the array of stubs that need PHP file extensions.
     *
     * @return array
     */
    protected function stubsToRename()
    {
        return [
            $this->componentPath().'/src/CardServiceProvider.stub',
            $this->componentPath().'/routes/api.stub',
        ];
    }

    /**
     * Get the "title" name of the card.
     *
     * @return string
     */
    protected function componentTitle()
    {
        return Str::title(str_replace('-', ' ', $this->componentName()));
    }
}
