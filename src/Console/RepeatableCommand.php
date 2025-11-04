<?php

namespace Laravel\Nova\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Illuminate\Filesystem\join_paths;
use function Laravel\Prompts\suggest;

#[AsCommand(name: 'nova:repeatable')]
class RepeatableCommand extends GeneratorCommand
{
    use ResolvesStubPath;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'nova:repeatable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repeatable class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Repeatable';

    /** {@inheritDoc} */
    #[\Override]
    protected function buildClass($name)
    {
        $resourceName = $this->argument('name');

        /** @var string|null $model */
        $model = $this->option('model');
        $modelNamespace = $this->getModelNamespace();

        if (\is_null($model)) {
            $model = $modelNamespace.str_replace('/', '\\', $resourceName);
        } elseif (! Str::startsWith($model, [
            $modelNamespace, '\\',
        ])) {
            $model = $modelNamespace.$model;
        }

        $replace = [
            'DummyFullModel' => $model,
            '{{ namespacedModel }}' => $model,
            '{{namespacedModel}}' => $model,
        ];

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('model')) {
            return $this->resolveStubPath('/stubs/nova/repeatable-model.stub');
        }

        return $this->resolveStubPath('/stubs/nova/repeatable.stub');
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function getDefaultNamespace($rootNamespace)
    {
        return match (true) {
            is_dir(app_path(join_paths('Nova', 'Repeater'))) => $rootNamespace.'\Nova\Repeater',
            default => $rootNamespace.'\Nova\Repeaters',
        };
    }

    /**
     * Get the default namespace for the class.
     *
     * @return string
     */
    protected function getModelNamespace()
    {
        $rootNamespace = $this->laravel->getNamespace();

        return is_dir(app_path('Models')) ? $rootNamespace.'Models\\' : $rootNamespace;
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     *
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->didReceiveOptions($input)) {
            return;
        }

        $model = suggest(
            'What model should this repeatable be for? (Optional)',
            $this->possibleModels()
        );

        if ($model) {
            $input->setOption('model', $model);
        }
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_REQUIRED, 'The model class being represented.'],
        ];
    }
}
