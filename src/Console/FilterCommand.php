<?php

namespace Laravel\Nova\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'nova:filter')]
class FilterCommand extends GeneratorCommand
{
    use ResolvesStubPath;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'nova:filter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new filter class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Filter';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('boolean')) {
            return $this->resolveStubPath('/stubs/nova/boolean-filter.stub');
        } elseif ($this->option('date')) {
            return $this->resolveStubPath('/stubs/nova/date-filter.stub');
        }

        return $this->resolveStubPath('/stubs/nova/filter.stub');
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Nova\Filters';
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function getOptions()
    {
        return [
            ['boolean', null, InputOption::VALUE_NONE, 'Indicates if the generated filter should be a boolean filter'],
            ['date', null, InputOption::VALUE_NONE, 'Indicates if the generated filter should be a date filter'],
        ];
    }
}
