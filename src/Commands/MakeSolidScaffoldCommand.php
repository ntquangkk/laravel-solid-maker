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
        $this->modelPlural = Str::plural($this->model);

        try {
            $this->setupPaths();
            $this->createDatabaseFiles();
            $this->createAppFiles();
            $this->createServiceFiles();
            $this->createTestFiles();
            $this->registerBindings();
            $this->registerRoutes();

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
            $stub = $this->getStub('migration');
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
            $stub = $this->getStub('factory');
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
            $stub = $this->getStub('seeder');
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
            $stub = $this->getStub('database-seeder');
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
        }
    }

    protected function createRepositoryInterface()
    {
        $path = $this->appPath . "/Repositories/Contracts/{$this->model}RepositoryInterface.php";

        if (! $this->files->exists($path)) {
            $stub = $this->getStub('repository-interface');
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
        $path = $this->appPath . "/Repositories/Eloquent/{$this->model}Repository.php";

        if (! $this->files->exists($path)) {
            $stub = $this->getStub('repository');
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
            $stub = $this->getStub('feature-test');
            $content = str_replace(
                ['{{baseNamespaceSlash}}', '{{appNamespace}}', '{{model}}', '{{modelVar}}', '{{modelPlural}}', '{{TEST_INCOMPLETE}}', '{{AUTO_GEN_FLAG}}'],
                [$this->baseNamespaceSlash, $this->appNamespace, $this->model, $this->modelVar, $this->modelPlural, self::TEST_INCOMPLETE, self::AUTO_GEN_FLAG],
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
            $stub = $this->getStub('unit-test');
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
            $stub = $this->getStub('provider');
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
                    "            \\{$this->appNamespace}\\Repositories\\Eloquent\\{$this->model}Repository::class\n" .
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
        $route = "\n" . self::AUTO_GEN_TAG . "\n{$routeType}('" . Str::snake($this->modelPlural) . "', \\{$this->appNamespace}\\Http\\Controllers\\{$this->model}Controller::class);\n";

        if (! $this->files->exists($routePath)) {
            $this->makeDirectory($routePath);
            $this->files->put($routePath, "<?php\n\nuse Illuminate\Support\Facades\Route;\n{$route}");
            $this->info("Created routes file: {$routePath}");
            Log::info("Created routes file: {$routePath}");
        } else {
            $content = $this->files->get($routePath);
            if (! str_contains($content, "{$this->model}Controller")) {
                $this->files->append($routePath, $route);
                $this->info("Appended routes to: {$routePath}");
                Log::info("Appended routes to: {$routePath}");
            }
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
