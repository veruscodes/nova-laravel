<?php

namespace Laravel\Nova\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'nova:trend')]
class TrendCommand extends GeneratorCommand
{
    use ResolvesStubPath;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'nova:trend';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new metric (trend) class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Metric';

    /** {@inheritDoc} */
    #[\Override]
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        $key = preg_replace('/[^a-zA-Z0-9]+/', '', $this->argument('name'));

        return str_replace('uri-key', Str::kebab($key), $stub);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/nova/trend.stub');
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Nova\Metrics';
    }
}
