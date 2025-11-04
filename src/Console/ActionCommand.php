<?php

namespace Laravel\Nova\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\confirm;

#[AsCommand(name: 'nova:action')]
class ActionCommand extends GeneratorCommand implements PromptsForMissingInput
{
    use ResolvesStubPath;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'nova:action';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new action class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Action';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $extension = $this->option('queued') ? 'queued.stub' : 'stub';

        if ($this->option('destructive')) {
            return $this->resolveStubPath("/stubs/nova/destructive-action.{$extension}");
        }

        return $this->resolveStubPath("/stubs/nova/action.{$extension}");
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Nova\Actions';
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

        $input->setOption('destructive', confirm(
            label: 'Indicate that the action deletes / destroys resources?',
            default: false,
        ));

        $input->setOption('queued', confirm(
            label: 'Indicates the action should be queued?',
            default: false,
        ));
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function getOptions()
    {
        return [
            ['destructive', null, InputOption::VALUE_NONE, 'Indicate that the action deletes / destroys resources'],
            ['queued', null, InputOption::VALUE_NONE, 'Indicates the action should be queued'],
        ];
    }
}
