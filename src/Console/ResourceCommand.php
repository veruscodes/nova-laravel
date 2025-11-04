<?php

namespace Laravel\Nova\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Str;
use Laravel\Nova\Util;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\suggest;

#[AsCommand(name: 'nova:resource')]
class ResourceCommand extends GeneratorCommand implements PromptsForMissingInput
{
    use ResolvesStubPath;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'nova:resource';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new resource class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Resource';

    /**
     * A list of resource names which are protected.
     *
     * @var array
     */
    protected $protectedNames = [
        'card',
        'cards',
        'dashboard',
        'dashboards',
        'metric',
        'metrics',
        'script',
        'scripts',
        'search',
        'searches',
        'style',
        'styles',
    ];

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    #[\Override]
    public function handle()
    {
        $this->callSilent('nova:base-resource', [
            'name' => 'Resource',
        ]);

        return parent::handle();
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function buildClass($name)
    {
        $resourceName = $this->argument('name');
        $model = $this->option('model');

        $modelNamespace = $this->getModelNamespace();

        if (\is_null($model)) {
            $model = $modelNamespace.str_replace('/', '\\', $resourceName);
        } elseif (! Str::startsWith($model, [
            $modelNamespace, '\\',
        ])) {
            $model = $modelNamespace.$model;
        }

        if (\in_array(strtolower($resourceName), $this->protectedNames)) {
            $this->components->warn("You *must* override the uriKey method for your {$resourceName} resource.");
        }

        $replace = [
            'DummyFullModel' => $model,
            '{{ namespacedModel }}' => $model,
            '{{namespacedModel}}' => $model,
        ];

        $result = str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );

        $baseResourceClass = $this->getBaseResourceClass();

        if (! class_exists($baseResourceClass)) {
            $baseResourceClass = 'Laravel\Nova\Resource';
        } elseif (! str_contains($resourceName, '/') && class_exists($baseResourceClass)) {
            return $result;
        }

        $eol = Util::eol($result);

        return str_replace(
            'use Laravel\Nova\Http\Requests\NovaRequest;'.$eol,
            'use Laravel\Nova\Http\Requests\NovaRequest;'.$eol."use {$baseResourceClass};".$eol,
            $result
        );
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/nova/resource.stub');
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Nova';
    }

    /**
     * Get the base resource class.
     *
     * @return class-string
     */
    protected function getBaseResourceClass()
    {
        $rootNamespace = $this->laravel->getNamespace();

        return "{$rootNamespace}Nova\Resource";
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
            'Which model is this resource for? (Optional)',
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
