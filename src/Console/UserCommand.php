<?php

namespace Laravel\Nova\Console;

use Illuminate\Console\Command;
use Laravel\Nova\Nova;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'nova:user')]
class UserCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'nova:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user';

    /** {@inheritDoc} */
    #[\Override]
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        (new Bootstrap\ConfiguresPrompts)->bootstrap($this->laravel);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        Nova::createUser($this);

        $this->components->info('User created successfully.');
    }
}
