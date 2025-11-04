<?php

namespace Laravel\Nova\Console;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'nova:field')]
class FieldCommand extends ComponentGeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nova:field {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new field';

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
            __DIR__.'/field-stubs',
            $this->componentPath()
        );

        // Field.js replacements...
        $files->replaceInFile('{{ component }}', $this->componentName(), $this->componentPath().'/resources/js/field.js');

        // Field.php replacements...
        $files->replaceInFile('{{ namespace }}', $this->componentNamespace(), $this->componentPath().'/src/Field.stub');
        $files->replaceInFile('{{ class }}', $this->componentClass(), $this->componentPath().'/src/Field.stub');
        $files->replaceInFile('{{ component }}', $this->componentName(), $this->componentPath().'/src/Field.stub');

        $files->move(
            $this->componentPath().'/src/Field.stub',
            $this->componentPath().'/src/'.$this->componentClass().'.php'
        );

        // FieldServiceProvider.php replacements...
        $files->replaceInFile('{{ namespace }}', $this->componentNamespace(), $this->componentPath().'/src/FieldServiceProvider.stub');
        $files->replaceInFile('{{ component }}', $this->componentName(), $this->componentPath().'/src/FieldServiceProvider.stub');

        // webpack.mix.js replacements...
        $files->replaceInFile('{{ name }}', $this->component(), $this->componentPath().'/webpack.mix.js');

        $files->move(
            $this->componentPath().'/src/FieldServiceProvider.stub',
            $this->componentPath().'/src/FieldServiceProvider.php'
        );

        // Field composer.json replacements...
        $this->prepareComposerReplacements($files);

        // Register the field...
        $this->buildComponent('field');
    }
}
