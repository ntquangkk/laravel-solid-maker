<?php

namespace TriQuang\LaravelSolidMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MakeSolidScaffoldCommand extends Command
{
    private const AUTO_GEN_FLAG = '// AUTO-GEN-4-SOLID';
    private const AUTO_GEN_TAG = '// AUTO-GEN: Placeholder';
    private const BINDINGS_MARKER = '// AUTO-GEN-BINDINGS';
    private const TEST_INCOMPLETE = 'AUTO-GEN: Placeholder - Incomplete test.';
    private const PUBLISHED_STUB_PATH = 'stubs/vendor/triquang/laravel-solid-maker';

    protected $signature = 'make:solid-scaffold {--model= : Model name} {--module= : Module name} {--view : Generate view routes}';

    protected $description = 'Generate SOLID architecture files for a model';

    protected $files;

    protected $model;

    protected $modelKebab;

    protected $modelPlural;

    protected $modelVar;

    protected $module;

    protected $hasView;

    protected $baseNamespaceSlash;

    protected $appNamespace;

    protected $basePath;

    protected $appPath;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $this->model = $this->option('model');
        $this->module = $this->option('module');
        $this->hasView = $this->option('view');

        if (! $this->model) {
            $this->error('Model name is required!');

            return self::FAILURE;
        }

        $this->modelVar = Str::camel($this->model);
        $this->modelKebab = Str::kebab($this->model);
        $this->modelPlural = Str::plural($this->model);

        try {
            $this->setupPaths();
            $this->createDatabaseFiles();
            $this->createAppFiles();
            $this->createServiceFiles();
            $this->createTestFiles();
            if ($this->module) {
                $this->registerModuleTestsInPhpunit();
            }
            $this->registerBindings();
            $this->registerRoutes();
            $this->registerPolicy();

            $this->info('SOLID files generated successfully!');
            Log::info('SOLID files generated successfully for model: ' . $this->model);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error generating SOLID files: ' . $e->getMessage());
            Log::error('Error generating SOLID files: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    protected function setupPaths()
    {
        $this->basePath = $this->module
            ? base_path("Modules/{$this->module}")
            : base_path();

        $this->appPath = $this->module
            ? $this->basePath . '/app'
            : base_path('app');

        $this->baseNamespaceSlash = $this->module
            ? "Modules\\{$this->module}\\"
            : '';

        $this->appNamespace = $this->module
            ? "Modules\\{$this->module}"
            : 'App';
    }

    protected function createDatabaseFiles()
    {
        $this->createMigration();
        $this->createFactory();
        $this->createSeeder();
    }

    protected function createAppFiles()
    {
        $this->createModel();
        $this->createController();
        $this->createRequests();
        $this->createResource();
        $this->createPolicy();
    }

    protected function createServiceFiles()
    {
        $this->createRepositoryInterface();
        $this->createRepository();
        $this->ensureServiceContractsDirectory();
        $this->createService();
    }

    protected function createTestFiles()
    {
        $this->createFeatureTest();
        $this->createUnitTest();
    }

    protected function createMigration()
    {
        $table = Str::snake($this->modelPlural);
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_create_{$table}_table.php";

        $path = $this->module
            ? $this->basePath . "/Database/Migrations/{$filename}"
            : database_path("migrations/{$filename}");

        if (! $this->files->exists($path)) {
            $stub = $this->getStub('database/migration');
            $content = str_replace(
                ['{{table}}', '{{model}}', '{{AUTO_GEN_TAG}}', '{{AUTO_GEN_FLAG}}'],
                [$table, $this->model, self::AUTO_GEN_TAG, self::AUTO_GEN_FLAG],
                $stub
            );

            $this->makeDirectory($path);
            $this->files->put($path, $content);
            $this->info("Created Migration: {$path}");
            Log::info("Created Migration: {$path}");
        }
    }

    protected function createFactory()
    {
        $path = $this->module
            ? $this->basePath . "/Database/Factories/{$this->model}Factory.php"
            : database_path("factories/{$this->model}Factory.php");

        if (! $this->files->exists($path)) {
            $stub = $this->getStub('database/factory');
            $content = str_replace(
                ['{{baseNamespaceSlash}}', '{{appNamespace}}', '{{model}}', '{{AUTO_GEN_TAG}}', '{{AUTO_GEN_FLAG}}'],
                [$this->baseNamespaceSlash, $this->appNamespace, $this->model, self::AUTO_GEN_TAG, self::AUTO_GEN_FLAG],
                $stub
            );

            $this->makeDirectory($path);
            $this->files->put($path, $content);
            $this->info("Created Factory: {$path}");
            Log::info("Created Factory: {$path}");
        }
    }

    protected function createSeeder()
    {
        $path = $this->module
            ? $this->basePath . "/Database/Seeders/{$this->model}Seeder.php"
            : database_path("seeders/{$this->model}Seeder.php");

        if (! $this->files->exists($path)) {
            $stub = $this->getStub('database/seeder');
            $content = str_replace(
                ['{{baseNamespaceSlash}}', '{{appNamespace}}', '{{model}}', '{{AUTO_GEN_TAG}}', '{{AUTO_GEN_FLAG}}'],
                [$this->baseNamespaceSlash, $this->appNamespace, $this->model, self::AUTO_GEN_TAG, self::AUTO_GEN_FLAG],
                $stub
            );

            $this->makeDirectory($path);
            $this->files->put($path, $content);
            $this->info("Created Seeder: {$path}");
            Log::info("Created Seeder: {$path}");

            $this->registerSeeder();
        }
    }

    protected function registerSeeder()
    {
        $seederPath = $this->module
            ? $this->basePath . "/Database/Seeders/{$this->module}DatabaseSeeder.php"
            : database_path("seeders/DatabaseSeeder.php");

        $seederClass = "{$this->baseNamespaceSlash}Database\\Seeders\\{$this->model}Seeder";
        $seederCall = "\n        \$this->call(\\{$seederClass}::class); " . self::AUTO_GEN_TAG;

        if (! $this->files->exists($seederPath)) {
            // Create a basic DatabaseSeeder if it doesn't exist
            $stub = $this->getStub('database/database-seeder');
            $content = str_replace(
                ['{{baseNamespaceSlash}}', '{{appNamespace}}', '{{module}}', '{{AUTO_GEN_FLAG}}', '{{SEEDER_CALLS}}'],
                [$this->baseNamespaceSlash, $this->appNamespace, $this->module ?? '', self::AUTO_GEN_FLAG, $seederCall],
                $stub
            );

            $this->makeDirectory($seederPath);
            $this->files->put($seederPath, $content);
            $this->info("Created DatabaseSeeder: {$seederPath}");
            Log::info("Created DatabaseSeeder: {$seederPath}");
        } else {
            $content = $this->files->get($seederPath);
            if (! str_contains($content, "{$this->model}Seeder")) {
                // Look for the run method to insert the seeder call
                $lines = explode("\n", $content);
                $inRunMethod = false;
                $insertLine = -1;

                foreach ($lines as $index => $line) {
                    if (preg_match('/public function run\(\)/', $line)) {
                        $inRunMethod = true;
                        continue;
                    }
                    if ($inRunMethod && preg_match('/^\s*}\s*$/', $line)) {
                        $insertLine = $index;
                        break;
                    }
                }

                if ($insertLine !== -1) {
                    // Insert the seeder call before the closing brace of the run method
                    array_splice($lines, $insertLine, 0, $seederCall);
                    $content = implode("\n", $lines);
                    $this->files->put($seederPath, $content);
                    $this->info("Registered {$this->model}Seeder in: {$seederPath}");
                    Log::info("Registered {$this->model}Seeder in: {$seederPath}");
                } else {
                    // Fallback: append to the end of the file with a warning
                    $content .= $seederCall;
                    $this->files->put($seederPath, $content);
                    $this->warn("Could not locate run method in {$seederPath}. Appended seeder call to the end. Please verify manually.");
                    Log::warning("Could not locate run method in {$seederPath}. Appended seeder call to the end.");
                }
            }
        }
    }

    protected function createModel()
    {
        $path = $this->appPath . "/Models/{$this->model}.php";

        if (! $this->files->exists($path)) {
            $stub = $this->getStub('model');
            $content = str_replace(
                ['{{baseNamespaceSlash}}', '{{appNamespace}}', '{{model}}', '{{AUTO_GEN_FLAG}}'],
                [$this->baseNamespaceSlash, $this->appNamespace, $this->model, self::AUTO_GEN_FLAG],
                $stub
            );

            $this->makeDirectory($path);
            $this->files->put($path, $content);
            $this->info("Created Model: {$path}");
            Log::info("Created Model: {$path}");
        } else {
            $content = $this->files->get($path);
            $lines = explode("\n", $content);
            $hasHasFactory = false;
            $hasNewFactory = false;
            $classStartIndex = -1;
            $classEndIndex = -1;
            $useInsertIndex = -1;
            $traitInsertIndex = -1;

            // Parse the file to find class boundaries and check for HasFactory/newFactory
            foreach ($lines as $index => $line) {
                if (str_contains($line, 'use Illuminate\Database\Eloquent\Factories\HasFactory;')) {
                    $hasHasFactory = true;
                }
                if ($this->module && str_contains($line, 'protected static function newFactory()')) {
                    $hasNewFactory = true;
                }
                if (preg_match('/^class\s+' . preg_quote($this->model, '/') . '\b/', $line)) {
                    $classStartIndex = $index;
                    // Look for the opening brace in the same or next lines
                    for ($i = $index; $i < count($lines); $i++) {
                        if (preg_match('/{/', $lines[$i])) {
                            $traitInsertIndex = $i + 1; // Insert traits after opening brace
                            break;
                        }
                    }
                }
                if ($classStartIndex !== -1 && preg_match('/^\s*}\s*$/', $line)) {
                    $classEndIndex = $index;
                    break;
                }
                if (preg_match('/^namespace\s+[^;]+;/', $line)) {
                    $useInsertIndex = $index + 1; // For use statements
                }
            }

            $modified = false;

            // Add HasFactory if missing
            if (! $hasHasFactory) {
                // Add use statement for HasFactory
                $useStatement = 'use Illuminate\Database\Eloquent\Factories\HasFactory;';
                if ($useInsertIndex !== -1) {
                    array_splice($lines, $useInsertIndex, 0, $useStatement);
                    if ($traitInsertIndex !== -1) {
                        $traitInsertIndex++; // Adjust for inserted use statement
                    }
                    if ($classEndIndex !== -1) {
                        $classEndIndex++; // Adjust for inserted use statement
                    }
                } else {
                    array_splice($lines, 1, 0, [$useStatement, '']);
                    if ($traitInsertIndex !== -1) {
                        $traitInsertIndex += 2; // Adjust for use statement and blank line
                    }
                    if ($classEndIndex !== -1) {
                        $classEndIndex += 2;
                    }
                }

                // Add HasFactory trait inside class body
                if ($traitInsertIndex !== -1) {
                    // Check if there’s an existing use statement for traits
                    $hasTraitUse = false;
                    for ($i = $traitInsertIndex; $i < ($classEndIndex !== -1 ? $classEndIndex : count($lines)); $i++) {
                        if (preg_match('/^\s*use\s+[^;]+;/', $lines[$i])) {
                            $hasTraitUse = true;
                            $lines[$i] = str_replace(';', ', HasFactory;', $lines[$i]);
                            break;
                        }
                    }
                    if (! $hasTraitUse) {
                        array_splice($lines, $traitInsertIndex, 0, '    use HasFactory;');
                        if ($classEndIndex !== -1) {
                            $classEndIndex++; // Adjust for inserted trait
                        }
                    }
                    $

                    $modified = true;
                    $this->info("Added HasFactory trait to: {$path}");
                    Log::info("Added HasFactory trait to: {$path}");
                } else {
                    $this->warn("Could not locate class opening in {$path}. Please add HasFactory trait manually.");
                    Log::warning("Could not locate class opening in {$path}. Please add HasFactory trait manually.");
                }
            }

            // Add newFactory method for modules if missing
            if ($this->module && ! $hasNewFactory && $classEndIndex !== -1) {
                $factoryNamespace = "\\{$this->appNamespace}\\Database\\Factories\\{$this->model}Factory";
                $newFactoryMethod = "\n    protected static function newFactory()\n    {\n        return {$factoryNamespace}::new();\n    }\n";
                array_splice($lines, $classEndIndex, 0, $newFactoryMethod);
                $modified = true;
                $this->info("Added newFactory method to: {$path}");
                Log::info("Added newFactory method to: {$path}");
            } elseif ($this->module && ! $hasNewFactory) {
                $this->warn("Could not locate class closing in {$path}. Please add newFactory method manually.");
                Log::warning("Could not locate class closing in {$path}. Please add newFactory method manually.");
            }

            if ($modified) {
                $content = implode("\n", $lines);
                $this->files->put($path, $content);
            }
        }
    }

    protected function createController()
    {
        $path = $this->appPath . "/Http/Controllers/{$this->model}Controller.php";

        if (! $this->files->exists($path)) {
            $stub = $this->getStub('controller');
            $content = str_replace(
                ['{{appNamespace}}', '{{model}}', '{{modelVar}}', '{{AUTO_GEN_TAG}}', '{{AUTO_GEN_FLAG}}'],
                [$this->appNamespace, $this->model, $this->modelVar, self::AUTO_GEN_TAG, self::AUTO_GEN_FLAG],
                $stub
            );

            $this->makeDirectory($path);
            $this->files->put($path, $content);
            $this->info("Created Controller: {$path}");
            Log::info("Created Controller: {$path}");
        }
    }

    protected function createRequests()
    {
        $types = ['Store', 'Update'];
        foreach ($types as $type) {
            $path = $this->appPath . "/Http/Requests/{$type}{$this->model}Request.php";

            if (! $this->files->exists($path)) {
                $stub = $this->getStub('request');
                $content = str_replace(
                    ['{{appNamespace}}', '{{model}}', '{{type}}', '{{AUTO_GEN_FLAG}}'],
                    [$this->appNamespace, $this->model, $type, self::AUTO_GEN_FLAG],
                    $stub
                );

                $this->makeDirectory($path);
                $this->files->put($path, $content);
                $this->info("Created Request: {$path}");
                Log::info("Created Request: {$path}");
            }
        }
    }

    protected function createResource()
    {
        $path = $this->appPath . "/Http/Resources/{$this->model}Resource.php";

        if (! $this->files->exists($path)) {
            $stub = $this->getStub('resource');
            $content = str_replace(
                ['{{appNamespace}}', '{{model}}', '{{AUTO_GEN_FLAG}}'],
                [$this->appNamespace, $this->model, self::AUTO_GEN_FLAG],
                $stub
            );

            $this->makeDirectory($path);
            $this->files->put($path, $content);
            $this->info("Created Resource: {$path}");
            Log::info("Created Resource: {$path}");
        }
    }

    protected function createPolicy()
    {
        $path = $this->appPath . "/Policies/{$this->model}Policy.php";

        if (! $this->files->exists($path)) {
            $stub = $this->getStub('policy');
            $content = str_replace(
                ['{{appNamespace}}', '{{model}}', '{{modelVar}}', '{{AUTO_GEN_FLAG}}'],
                [$this->appNamespace, $this->model, $this->modelVar, self::AUTO_GEN_FLAG],
                $stub
            );

            $this->makeDirectory($path);
            $this->files->put($path, $content);
            $this->info("Created Policy: {$path}");
            Log::info("Created Policy: {$path}");
        } else {
            $this->info("Policy {$this->model}Policy already exists at {$path}");
            Log::info("Policy {$this->model}Policy already exists at {$path}");
        }
    }

    protected function createRepositoryInterface()
    {
        $path = $this->appPath . "/Repositories/Contracts/{$this->model}RepositoryInterface.php";

        if (! $this->files->exists($path)) {
            $stub = $this->getStub('repositories/repository-interface');
            $content = str_replace(
                ['{{appNamespace}}', '{{model}}', '{{AUTO_GEN_TAG}}', '{{AUTO_GEN_FLAG}}'],
                [$this->appNamespace, $this->model, self::AUTO_GEN_TAG, self::AUTO_GEN_FLAG],
                $stub
            );

            $this->makeDirectory($path);
            $this->files->put($path, $content);
            $this->info("Created Repository Interface: {$path}");
            Log::info("Created Repository Interface: {$path}");
        }
    }

    protected function createRepository()
    {
        $path = $this->appPath . "/Repositories/{$this->model}Repository.php";

        if (! $this->files->exists($path)) {
            $stub = $this->getStub('repositories/repository');
            $content = str_replace(
                ['{{appNamespace}}', '{{model}}', '{{AUTO_GEN_TAG}}', '{{AUTO_GEN_FLAG}}'],
                [$this->appNamespace, $this->model, self::AUTO_GEN_TAG, self::AUTO_GEN_FLAG],
                $stub
            );

            $this->makeDirectory($path);
            $this->files->put($path, $content);
            $this->info("Created Repository: {$path}");
            Log::info("Created Repository: {$path}");
        }
    }

    protected function ensureServiceContractsDirectory()
    {
        $directory = $this->appPath . '/Services/Contracts';

        if (! $this->files->exists($directory)) {
            $this->files->makeDirectory($directory, 0755, true, true);
            $this->info("Created directory: {$directory}");
            Log::info("Created directory: {$directory}");
        }
    }

    protected function createService()
    {
        $path = $this->appPath . "/Services/{$this->model}Service.php";

        if (! $this->files->exists($path)) {
            $stub = $this->getStub('service');
            $content = str_replace(
                ['{{appNamespace}}', '{{model}}', '{{AUTO_GEN_TAG}}', '{{AUTO_GEN_FLAG}}'],
                [$this->appNamespace, $this->model, self::AUTO_GEN_TAG, self::AUTO_GEN_FLAG],
                $stub
            );

            $this->makeDirectory($path);
            $this->files->put($path, $content);
            $this->info("Created Service: {$path}");
            Log::info("Created Service: {$path}");
        }
    }

    protected function createFeatureTest()
    {
        $path = $this->module
            ? $this->basePath . "/Tests/Feature/{$this->model}Test.php"
            : base_path("tests/Feature/{$this->model}Test.php");

        if (! $this->files->exists($path)) {
            $stub = $this->getStub('tests/feature-test');
            $content = str_replace(
                ['{{baseNamespaceSlash}}', '{{appNamespace}}', '{{model}}', '{{modelVar}}', '{{modelKebab}}', '{{TEST_INCOMPLETE}}', '{{AUTO_GEN_FLAG}}'],
                [$this->baseNamespaceSlash, $this->appNamespace, $this->model, $this->modelVar, $this->modelKebab, self::TEST_INCOMPLETE, self::AUTO_GEN_FLAG],
                $stub
            );

            $this->makeDirectory($path);
            $this->files->put($path, $content);
            $this->info("Created Feature Test: {$path}");
            Log::info("Created Feature Test: {$path}");
        }
    }

    protected function createUnitTest()
    {
        $path = $this->module
            ? $this->basePath . "/Tests/Unit/{$this->model}ServiceTest.php"
            : base_path("tests/Unit/{$this->model}ServiceTest.php");

        if (! $this->files->exists($path)) {
            $stub = $this->getStub('tests/unit-test');
            $content = str_replace(
                ['{{baseNamespaceSlash}}', '{{appNamespace}}', '{{model}}', '{{modelVar}}', '{{TEST_INCOMPLETE}}', '{{AUTO_GEN_FLAG}}'],
                [$this->baseNamespaceSlash, $this->appNamespace, $this->model, $this->modelVar, self::TEST_INCOMPLETE, self::AUTO_GEN_FLAG],
                $stub
            );

            $this->makeDirectory($path);
            $this->files->put($path, $content);
            $this->info("Created Unit Test: {$path}");
            Log::info("Created Unit Test: {$path}");
        }
    }

    protected function registerBindings()
    {
        $providerPath = $this->module
            ? $this->appPath . '/Providers/RepositoryServiceProvider.php'
            : app_path('Providers/RepositoryServiceProvider.php');

        if (! $this->files->exists($providerPath)) {
            $stub = $this->getStub('providers/repository-provider');
            $content = str_replace(
                ['{{appNamespace}}', '{{BINDINGS_MARKER}}', '{{AUTO_GEN_FLAG}}'],
                [$this->appNamespace, self::BINDINGS_MARKER, self::AUTO_GEN_FLAG],
                $stub
            );
            $this->makeDirectory($providerPath);
            $this->files->put($providerPath, $content);
            $this->info("Created RepositoryServiceProvider: {$providerPath}");
            Log::info("Created RepositoryServiceProvider: {$providerPath}");
        }

        $bindings = "\n        \$this->app->bind(\n" .
                    "            \\{$this->appNamespace}\\Repositories\\Contracts\\{$this->model}RepositoryInterface::class,\n" .
                    "            \\{$this->appNamespace}\\Repositories\\{$this->model}Repository::class\n" .
                    '        );';

        $content = $this->files->get($providerPath);
        if (! str_contains($content, "{$this->model}RepositoryInterface")) {
            // Look for the BINDINGS_MARKER or the end of the register method
            if (str_contains($content, self::BINDINGS_MARKER)) {
                $content = str_replace(self::BINDINGS_MARKER, self::BINDINGS_MARKER . $bindings, $content);
            } else {
                // Parse the file to find the register method
                $lines = explode("\n", $content);
                $inRegisterMethod = false;
                $insertLine = -1;

                foreach ($lines as $index => $line) {
                    if (preg_match('/public function register\(\)/', $line)) {
                        $inRegisterMethod = true;

                        continue;
                    }
                    if ($inRegisterMethod && preg_match('/^\s*}\s*$/', $line)) {
                        $insertLine = $index;
                        break;
                    }
                }

                if ($insertLine !== -1) {
                    // Insert bindings before the closing brace of the register method
                    array_splice($lines, $insertLine, 0, '        ' . self::BINDINGS_MARKER . $bindings);
                    $content = implode("\n", $lines);
                } else {
                    // Fallback: append to the end of the file with a warning
                    $content .= "\n" . self::BINDINGS_MARKER . $bindings;
                    $this->warn("Could not locate register method in {$providerPath}. Appended bindings to the end. Please verify manually.");
                    Log::warning("Could not locate register method in {$providerPath}. Appended bindings to the end.");
                }
            }

            $this->files->put($providerPath, $content);
            $this->info("Registered repository bindings in: {$providerPath}");
            Log::info("Registered repository bindings in: {$providerPath}");
        }

        // Register provider in bootstrap/providers.php or module.json
        if (! $this->module) {
            $bootstrapProvidersPath = base_path('bootstrap/providers.php');
            if ($this->files->exists($bootstrapProvidersPath)) {
                $providerClass = "{$this->appNamespace}\\Providers\\RepositoryServiceProvider::class";
                $content = $this->files->get($bootstrapProvidersPath);

                if (! str_contains($content, $providerClass)) {
                    // Parse the providers array and append the new provider
                    $lines = explode("\n", $content);
                    $lastArrayLine = -1;
                    foreach ($lines as $index => $line) {
                        if (str_contains($line, '];')) {
                            $lastArrayLine = $index;
                            break;
                        }
                    }

                    if ($lastArrayLine > 0) {
                        array_splice($lines, $lastArrayLine, 0, "    {$providerClass},");
                        $newContent = implode("\n", $lines);
                        $this->files->put($bootstrapProvidersPath, $newContent);
                        $this->info("Registered RepositoryServiceProvider in: {$bootstrapProvidersPath}");
                        Log::info("Registered RepositoryServiceProvider in: {$bootstrapProvidersPath}");
                    } else {
                        $this->error("Could not find array closing in {$bootstrapProvidersPath}. Please add {$providerClass} manually.");
                        Log::error("Could not find array closing in {$bootstrapProvidersPath}. Please add {$providerClass} manually.");
                    }
                }
            } else {
                $this->error("File {$bootstrapProvidersPath} does not exist. Please create it or add {$this->appNamespace}\\Providers\\RepositoryServiceProvider::class manually.");
                Log::error("File {$bootstrapProvidersPath} does not exist. Please create it or add {$this->appNamespace}\\Providers\\RepositoryServiceProvider::class manually.");
            }
        } else {
            // Handle module registration
            $moduleJsonPath = $this->basePath . '/module.json';
            $providerClass = "{$this->appNamespace}\\Providers\\RepositoryServiceProvider";

            if (! $this->files->exists($moduleJsonPath)) {
                // Create a basic module.json if it doesn't exist
                $moduleConfig = [
                    'name' => $this->module,
                    'alias' => Str::snake($this->module),
                    'description' => '',
                    'providers' => [$providerClass],
                ];
                $this->makeDirectory($moduleJsonPath);
                $this->files->put($moduleJsonPath, json_encode($moduleConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $this->info("Created module.json with RepositoryServiceProvider: {$moduleJsonPath}");
                Log::info("Created module.json with RepositoryServiceProvider: {$moduleJsonPath}");
            } else {
                $moduleConfig = json_decode($this->files->get($moduleJsonPath), true);
                if (! is_array($moduleConfig)) {
                    $this->error("Invalid module.json at {$moduleJsonPath}. Please add {$providerClass} to providers array manually.");
                    Log::error("Invalid module.json at {$moduleJsonPath}. Please add {$providerClass} to providers array manually.");

                    return;
                }

                if (! isset($moduleConfig['providers']) || ! in_array($providerClass, $moduleConfig['providers'])) {
                    $moduleConfig['providers'][] = $providerClass;
                    $this->files->put($moduleJsonPath, json_encode($moduleConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    $this->info("Registered RepositoryServiceProvider in: {$moduleJsonPath}");
                    Log::info("Registered RepositoryServiceProvider in: {$moduleJsonPath}");
                }
            }
        }
    }

    protected function registerRoutes()
    {
        $routeFile = $this->hasView ? 'web.php' : 'api.php';
        $routePath = $this->module
            ? $this->basePath . "/Routes/{$routeFile}"
            : base_path("routes/{$routeFile}");

        $routeType = $this->hasView ? 'Route::resource' : 'Route::apiResource';
        $route = "\n" . self::AUTO_GEN_TAG . "\n{$routeType}('{$this->modelKebab}', {$this->model}Controller::class)->names('{$this->modelKebab}');";

        // Define the use statement for the controller
        $controllerClass = "{$this->appNamespace}\\Http\\Controllers\\{$this->model}Controller";
        $useControllerStatement = "use {$controllerClass};";

        if (! $this->files->exists($routePath)) {
            // Create new routes file with use statements and route
            $content = "<?php\n\nuse Illuminate\Support\Facades\Route;\n{$useControllerStatement};\n{$route}";
            $this->makeDirectory($routePath);
            $this->files->put($routePath, $content);
            $this->info("Created routes file: {$routePath}");
            Log::info("Created routes file: {$routePath}");
            return;
        }

        $content = $this->files->get($routePath);

        // Check if the controller use statement is present
        $lines = explode("\n", $content);
        $hasControllerUse = false;
        $useInsertIndex = -1;

        foreach ($lines as $index => $line) {
            if (str_contains($line, $useControllerStatement)) {
                $hasControllerUse = true;
                break;
            }
            if (preg_match('/^use\s+Illuminate\\\Support\\\Facades\\\Route;/', $line)) {
                $useInsertIndex = $index + 1; // Insert after Route use statement
            }
        }

        // Add use statement if missing
        if (! $hasControllerUse) {
            if ($useInsertIndex !== -1) {
                array_splice($lines, $useInsertIndex, 0, $useControllerStatement);
            } else {
                // Fallback: add at the top after <?php
                array_splice($lines, 1, 0, [$useControllerStatement, '']);
            }
            $content = implode("\n", $lines);
            $this->files->put($routePath, $content);
            $this->info("Added use statement for {$this->model}Controller in: {$routePath}");
            Log::info("Added use statement for {$this->model}Controller in: {$routePath}");
        }

        // Check if the route is already registered
        if (str_contains($content, "{$this->model}Controller") && str_contains($content, "'{$this->modelKebab}'")) {
            $this->info("Route for {$this->modelKebab} already registered in: {$routePath}");
            Log::info("Route for {$this->modelKebab} already registered in: {$routePath}");
            return;
        }

        // Append the route
        $this->files->append($routePath, $route);
        $this->info("Appended route for {$this->modelKebab} to: {$routePath}");
        Log::info("Appended route for {$this->modelKebab} to: {$routePath}");
    }

    protected function registerModuleTestsInPhpunit()
    {
        $phpunitPath = base_path('phpunit.xml');

        if (! $this->files->exists($phpunitPath)) {
            $this->warn("phpunit.xml not found at {$phpunitPath}. Skipping module test registration.");
            Log::warning("phpunit.xml not found at {$phpunitPath}. Skipping module test registration.");
            return;
        }

        $content = $this->files->get($phpunitPath);

        // Check if already registered (exact string match for simplicity)
        if (str_contains($content, '<directory>Modules/*/tests/Unit</directory>') &&
            str_contains($content, '<directory>Modules/*/tests/Feature</directory>')) {
            $this->info('Module test directories already registered in phpunit.xml');
            Log::info('Module test directories already registered in phpunit.xml');
            return;
        }

        $lines = explode("\n", $content);

        // Directories to add (with standard Laravel indentation: 12 spaces)
        $unitDir = '            <directory>Modules/*/tests/Unit</directory>';
        $featureDir = '            <directory>Modules/*/tests/Feature</directory>';

        // Find insert positions for Unit and Feature testsuites
        $unitInsertLine = -1;
        $featureInsertLine = -1;
        $inUnitSuite = false;
        $inFeatureSuite = false;

        foreach ($lines as $index => $line) {
            $trimmedLine = trim($line);

            if (strpos($trimmedLine, '<testsuite name="Unit">') === 0) {
                $inUnitSuite = true;
                continue;
            }
            if ($inUnitSuite && $trimmedLine === '</testsuite>') {
                $unitInsertLine = $index;
                $inUnitSuite = false;
            }

            if (strpos($trimmedLine, '<testsuite name="Feature">') === 0) {
                $inFeatureSuite = true;
                continue;
            }
            if ($inFeatureSuite && $trimmedLine === '</testsuite>') {
                $featureInsertLine = $index;
                $inFeatureSuite = false;
            }
        }

        // Insert if positions found
        $inserted = false;

        if ($unitInsertLine !== -1 && !str_contains($content, '<directory>Modules/*/tests/Unit</directory>')) {
            array_splice($lines, $unitInsertLine, 0, $unitDir);
            $inserted = true;
            // Adjust feature insert line if it was after unit (due to line shift)
            if ($featureInsertLine > $unitInsertLine) {
                $featureInsertLine++;
            }
        }

        if ($featureInsertLine !== -1 && !str_contains($content, '<directory>Modules/*/tests/Feature</directory>')) {
            array_splice($lines, $featureInsertLine, 0, $featureDir);
            $inserted = true;
        }

        if ($inserted) {
            $newContent = implode("\n", $lines);
            $this->files->put($phpunitPath, $newContent);
            $this->info('Registered module test directories in phpunit.xml');
            Log::info('Registered module test directories in phpunit.xml');
        } else {
            $this->warn('Could not find Unit or Feature testsuite sections in phpunit.xml. Please add module directories manually.');
            Log::warning('Could not find Unit or Feature testsuite sections in phpunit.xml. Please add module directories manually.');
        }
    }

    protected function registerPolicy()
    {
        // Determine the ServiceProvider path
        $providerPath = $this->module
            ? $this->appPath . "/Providers/{$this->module}ServiceProvider.php"
            : app_path('Providers/AppServiceProvider.php');

        // Define the Policy class and registration code
        $policyClass = "\\{$this->appNamespace}\\Policies\\{$this->model}Policy";
        $modelClass = "\\{$this->appNamespace}\\Models\\{$this->model}";
        $policyRegistration = "\n        Gate::policy({$modelClass}::class, {$policyClass}::class); // AUTO-GEN-POLICY";

        // Check if the ServiceProvider exists; if not, create it
        if (! $this->files->exists($providerPath)) {
            $stub = $this->getStub('providers/module-service-provider');
            $content = str_replace(
                ['{{appNamespace}}', '{{module}}', '{{AUTO_GEN_FLAG}}', '{{POLICY_REGISTRATION}}'],
                [$this->appNamespace, $this->module ?? 'App', self::AUTO_GEN_FLAG, $policyRegistration],
                $stub
            );

            $this->makeDirectory($providerPath);
            $this->files->put($providerPath, $content);
            $this->info("Created ServiceProvider with Policy registration: {$providerPath}");
            Log::info("Created ServiceProvider with Policy registration: {$providerPath}");
            return;
        }

        // Get the ServiceProvider content
        $content = $this->files->get($providerPath);

        // Check if the Policy is already registered
        if (str_contains($content, "{$this->model}Policy")) {
            $this->info("Policy {$this->model}Policy already registered in {$providerPath}");
            Log::info("Policy {$this->model}Policy already registered in {$providerPath}");
            return;
        }

        // Check if 'use Illuminate\Support\Facades\Gate;' is present
        $useGateStatement = "use Illuminate\Support\Facades\Gate;";
        $lines = explode("\n", $content);
        $hasGateUse = false;
        $namespaceLineIndex = -1;
        $insertUseIndex = -1;

        foreach ($lines as $index => $line) {
            if (str_contains($line, $useGateStatement)) {
                $hasGateUse = true;
                break;
            }
            if (preg_match('/^namespace\s+[^;]+;/', $line)) {
                $namespaceLineIndex = $index;
            }
            if ($namespaceLineIndex !== -1 && preg_match('/^\s*$/', $line) && $insertUseIndex === -1) {
                $insertUseIndex = $index + 1; // Insert after namespace and a blank line
            }
        }

        // Add 'use' statement if missing
        if (! $hasGateUse) {
            if ($insertUseIndex !== -1) {
                array_splice($lines, $insertUseIndex, 0, $useGateStatement);
            } else {
                // Fallback: add after namespace or at the start of the file
                if ($namespaceLineIndex !== -1) {
                    array_splice($lines, $namespaceLineIndex + 1, 0, [$useGateStatement, '']);
                } else {
                    array_splice($lines, 1, 0, [$useGateStatement, '']);
                }
                $this->warn("Added missing 'use Illuminate\Support\Facades\Gate;' to {$providerPath}");
                Log::warning("Added missing 'use Illuminate\Support\Facades\Gate;' to {$providerPath}");
            }
            $content = implode("\n", $lines);
            $this->files->put($providerPath, $content);
        }

        // Find the boot method to insert the policy registration
        $inBootMethod = false;
        $insertLine = -1;

        $lines = explode("\n", $content); // Reload lines if modified
        foreach ($lines as $index => $line) {
            if (preg_match('/public function boot\(\)/', $line)) {
                $inBootMethod = true;
                continue;
            }
            if ($inBootMethod && preg_match('/^\s*}\s*$/', $line)) {
                $insertLine = $index;
                break;
            }
        }

        if ($insertLine !== -1) {
            // Insert the policy registration before the closing brace of the boot method
            array_splice($lines, $insertLine, 0, $policyRegistration);
            $content = implode("\n", $lines);
            $this->files->put($providerPath, $content);
            $this->info("Registered {$this->model}Policy in: {$providerPath}");
            Log::info("Registered {$this->model}Policy in: {$providerPath}");
        } else {
            // Fallback: append to the end of the file with a warning
            $content .= "\n" . $policyRegistration;
            $this->files->put($providerPath, $content);
            $this->warn("Could not locate boot method in {$providerPath}. Appended policy registration to the end. Please verify manually.");
            Log::warning("Could not locate boot method in {$providerPath}. Appended policy registration to the end.");
        }
    }

    protected function makeDirectory($path)
    {
        $this->files->makeDirectory(dirname($path), 0755, true, true);
    }

    protected function getStub($name)
    {
        // First, try to load from the project's published stubs
        $customStubPath = base_path(self::PUBLISHED_STUB_PATH . "/{$name}.stub");
        if ($this->files->exists($customStubPath)) {
            return $this->files->get($customStubPath);
        }
        // Fallback to package stubs
        $stubPath = __DIR__ . "/../../stubs/{$name}.stub";
        if (! $this->files->exists($stubPath)) {
            throw new \Exception("Stub file for {$name} not found at {$stubPath}");
        }

        return $this->files->get($stubPath);
    }
}
