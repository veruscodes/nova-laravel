<?php

namespace Laravel\Nova\Console;

use Illuminate\Container\Container;
use Illuminate\Support\Str;
use Laravel\Nova\Events\NovaServiceProviderRegistered;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Util;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\suggest;

#[AsCommand(name: 'nova:policy')]
class PolicyMakeCommand extends \Illuminate\Foundation\Console\PolicyMakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'nova:policy';

    /** {@inheritDoc} */
    #[\Override]
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        $stub = $this->replaceUserNamespace(
            $this->replaceNamespace($stub, $name)->replaceClass($stub, $name)
        );

        $resource = $this->option('resource');

        return $this->replaceNovaResource($stub, $resource ?? 'Resource');
    }

    /**
     * Replace the model for the given stub.
     */
    protected function replaceNovaResource(string $stub, string $resource): string
    {
        $resource = str_replace('/', '\\', $resource);

        if (str_starts_with($resource, '\\')) {
            $namespacedResource = trim($resource, '\\');
        } else {
            $namespacedResource = $this->qualifyNovaResource($resource);
        }

        $resource = class_basename(trim($resource, '\\'));

        $dummyModel = $resource;

        $dummyUser = class_basename($this->userProviderModel());

        if ($dummyUser === $resource) {
            $resource = 'UserResource';
            $namespacedResource = "{$namespacedResource} as {$resource}";
            $dummyModel = 'resource';
        }

        $replace = [
            'NamespacedDummyModel' => $namespacedResource,
            '{{ namespacedModel }}' => $namespacedResource,
            '{{namespacedModel}}' => $namespacedResource,
            'DummyModel' => $resource,
            '{{ model }}' => $resource,
            '{{model}}' => $resource,
            'dummyModel' => Str::camel($dummyModel),
            '{{ modelVariable }}' => Str::camel($dummyModel),
            '{{modelVariable}}' => Str::camel($dummyModel),
            'DummyUser' => $dummyUser,
            '{{ user }}' => $dummyUser,
            '{{user}}' => $dummyUser,
            '$user' => '$'.Str::camel($dummyUser),
        ];

        $stub = str_replace(
            array_keys($replace), array_values($replace), $stub
        );

        return preg_replace(
            vsprintf('/use %s;[\r\n]+use %s;/', [
                preg_quote($namespacedResource, '/'),
                preg_quote($namespacedResource, '/'),
            ]),
            "use {$namespacedResource};",
            $stub
        );
    }

    /**
     * Qualify the given model class base name.
     */
    protected function qualifyNovaResource(string $resource): string
    {
        $resource = ltrim($resource, '\\/');

        $resource = str_replace('/', '\\', $resource);

        $rootNamespace = $this->rootNamespace();

        if (str_starts_with($resource, $rootNamespace)) {
            return $resource;
        }

        return $rootNamespace.'Nova\\'.$resource;
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/policy.stub');
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function resolveStubPath($stub)
    {
        if (file_exists($customPath = $this->laravel->basePath('/stubs/nova/policy.stub'))) {
            return $customPath;
        }

        return parent::resolveStubPath($stub);
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function userProviderModel()
    {
        return Util::userModel();
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Nova\Policies';
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the policy already exists'],
            ['resource', 'm', InputOption::VALUE_OPTIONAL, 'The resource that the policy applies to'],
        ];
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->isReservedName($this->getNameInput()) || $this->didReceiveOptions($input)) {
            return;
        }

        /** @var \Illuminate\Contracts\Foundation\Application $app */
        $app = Container::getInstance();

        ServingNova::dispatch($app, NovaRequest::create('/', 'OPTIONS'));
        NovaServiceProviderRegistered::dispatch();

        $resourceClass = suggest(
            'What resource should this policy apply to? (Optional)',
            $this->possibleNovaResources(),
        );

        if ($resourceClass) {
            if (! str_starts_with($this->rootNamespace(), $resourceClass) && class_exists($resourceClass)) {
                $resourceClass = \sprintf('\%s', $resourceClass);
            }

            $input->setOption('resource', $resourceClass);
        }
    }

    /**
     * Get a list of possible model names.
     *
     * @return array<int, class-string>
     */
    protected function possibleNovaResources(): array
    {
        return Nova::resourceCollection()->all();
    }
}
