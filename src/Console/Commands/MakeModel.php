<?php
/**
 * This class is responsible for creating a new Firestore Eloquent model class.
 * It extends the `GeneratorCommand` and uses the trait `CreatesMatchingTest`.
 *
 * This class has the following public methods:
 *
 * @method string getNameInput() Build the class with the given name.
 * @method string getStub() Get the stub file for the generator.
 * @method string getDefaultNamespace(string $rootNamespace) Get the default namespace for the class.
 * @method array getOptions() Get the console command options.
 */

namespace Roddy\FirestoreEloquent\Console\Commands;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:fmodel')]
class MakeModel extends GeneratorCommand
{
    use CreatesMatchingTest;

    protected $signature = 'make:fmodel {name} {--f|force} {--c|collection= : The collection name of the model} {--p|primaryKey= : The model primary key}';

    protected $description = 'Create a new Firestore Eloquent model class';

    protected $type = 'Model';

    protected function getNameInput()
    {
        return str_replace('.', '/', trim($this->argument('name')));
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        $primaryKey = $this->option('primaryKey') ? $this->option('primaryKey') : 'id';
        $collection = $this->option('collection') ? $this->option('collection') : $this->getNameInput().'s';

        $collection = str_replace('=', '', $collection);
        $primaryKey = str_replace('=', '', $primaryKey);

        $stub = str_replace('{{ collection }}', $collection, $stub);
        $stub = str_replace('{{ primaryKey }}', $primaryKey, $stub);

        return $stub;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/firestore.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
                        ? $customPath
                        : __DIR__.$stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return config('firebase.class_namespace') ? config('firebase.class_namespace') : $rootNamespace.'\\FModels';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
            ['primaryKey', 'p', InputOption::VALUE_REQUIRED, 'The model primary key'],
            ['collection', 'c', InputOption::VALUE_REQUIRED, 'The model collection name'],
        ];
    }
}
