<?php

namespace Laravel\Nova\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'nova:translate')]
class TranslateCommand extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nova:translate
                                {language}
                                {--force : Overwrite any existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create translation files for Nova';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(Filesystem $files)
    {
        $language = $this->argument('language');

        $jsonLanguageFile = lang_path("vendor/nova/{$language}.json");

        if (! $files->exists($jsonLanguageFile) || $this->option('force')) {
            $files->copy(__DIR__.'/../../resources/lang/en.json', $jsonLanguageFile);
        }
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'language' => "What's the language name?",
        ];
    }
}
