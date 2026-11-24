<?php

namespace Dcp\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeDcpWorker extends Command
{
    protected $signature = 'make:dcp-worker {name}';
    protected $description = 'Create a new DCP Worker class';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $directory = app_path('Console/Commands/Dcp/Workers');
        $path = $directory . "/{$name}.php";

        // Check if the directory exists; if not, create it
        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        // Check if the file already exists
        if ($this->files->exists($path)) {
            $this->error("Worker already exists!");
            return Command::FAILURE;
        }

        // Load the stub, replace placeholders, and write to the new file
        $stubPath = realpath(__DIR__ . '/../../stubs/dcp-worker.stub');
        $stub = str_replace([
            '{{ class }}',
            '{{ kebab-class }}'
        ], [
            $name,
            Str::kebab($name)
        ], $this->files->get($stubPath));

        $this->files->put($path, $stub);

        $this->info("Worker created successfully at {$path}");

        return Command::SUCCESS;
    }
}
