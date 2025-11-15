<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str; // Per PascalCase e altre utilità stringa

// Importa le funzioni di Laravel Prompts
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

class ScaffoldNewModuleCommand extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scaffold:new-module {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold a new module with the given name';

    /**
     * Get the Casing of the module name.
     */
    private string $moduleNamePascalCase;

    private string $moduleNameKebabCase;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var string $nameArgument */
        $nameArgument = $this->argument('name');

        $this->moduleNamePascalCase = Str::studly($nameArgument);
        $this->moduleNameKebabCase = Str::kebab($this->moduleNamePascalCase);

        $moduleBasePath = "modules/{$this->moduleNamePascalCase}";
        $moduleAbsolutePath = base_path($moduleBasePath);

        if (File::isDirectory($moduleAbsolutePath)) {
            warning("Module {$this->moduleNamePascalCase} already exists at {$moduleBasePath}.");

            return Command::FAILURE;
        }

        $this->createDirectories($moduleBasePath);

        $this->createRouteFiles($moduleBasePath);

        $this->createServiceProvider($moduleBasePath);

        info("Module {$this->moduleNamePascalCase} created successfully at {$moduleBasePath}.");

        $this->displayNextSteps();

        return Command::SUCCESS;
    }

    protected function getServiceProviderStub(string $name, string $kebabName): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

class {$name}ServiceProvider extends ServiceProvider
{
    /**
     * The base path of the module.
     *
     * @var string
     */
    protected string \$moduleBasePath;

    public function __construct(\$app)
    {
        parent::__construct(\$app);
        \$this->moduleBasePath = __DIR__ . '/../../';
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \$this->loadRoutes();
        \$this->loadMigrations();
        // \$this->loadTranslations();
        // \$this->loadViews();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register module-specific services, configurations, etc.
        // \$this->mergeConfigFrom(\$this->moduleBasePath . 'config/{$kebabName}.php', '{$kebabName}');
    }

    /**
     * Load routes for the module.
     */
    protected function loadRoutes(): void
    {
        if (File::exists(\$this->moduleBasePath . 'routes/web.php')) {
            Route::middleware('web')
                // ->prefix('{$kebabName}') // Uncomment if you want a base prefix for all web routes
                ->group(\$this->moduleBasePath . 'routes/web.php');
        }

        if (File::exists(\$this->moduleBasePath . 'routes/api.php')) {
            Route::prefix('api') // All API routes are typically prefixed with 'api'
                // ->middleware('api') // Uncomment if you have specific API middleware group
                ->group(\$this->moduleBasePath . 'routes/api.php');
        }

        // For console routes, they are typically loaded automatically if this provider is registered
        // or you can load them explicitly if needed, though commands are usually discovered.
        // If you have commands defined in module's routes/console.php, uncomment:
        // if (\$this->app->runningInConsole() && File::exists(\$this->moduleBasePath . 'routes/console.php')) {
        //     require \$this->moduleBasePath . 'routes/console.php';
        // }
    }

    /**
     * Load migrations for the module.
     */
    protected function loadMigrations(): void
    {
        if (File::exists(\$this->moduleBasePath . 'database/migrations')) {
            \$this->loadMigrationsFrom(\$this->moduleBasePath . 'database/migrations');
        }
    }

    /**
     * Load translations for the module.
     */
    protected function loadTranslations(): void
    {
        if (File::exists(\$this->moduleBasePath . 'resources/lang')) {
            \$this->loadTranslationsFrom(\$this->moduleBasePath . 'resources/lang', '{$kebabName}');
        }
    }

    /**
     * Load views for the module.
     */
    protected function loadViews(): void
    {
        if (File::exists(\$this->moduleBasePath . 'resources/views')) {
            \$this->loadViewsFrom(\$this->moduleBasePath . 'resources/views', '{$kebabName}');
        }
    }

}
PHP;
    }

    private function createDirectories(string $moduleBasePath): void
    {
        $paths = [
            "{$moduleBasePath}/src/Http/Controllers",
            "{$moduleBasePath}/src/Http/Middleware",
            "{$moduleBasePath}/src/Models",
            "{$moduleBasePath}/src/Providers",
            "{$moduleBasePath}/src/Console/Commands",
            "{$moduleBasePath}/database/migrations",
            "{$moduleBasePath}/database/seeders",
            "{$moduleBasePath}/database/factories",
            "{$moduleBasePath}/routes",
            "{$moduleBasePath}/resources/views",
            "{$moduleBasePath}/resources/lang",
            "{$moduleBasePath}/tests/Feature",
            "{$moduleBasePath}/tests/Unit",
        ];

        foreach ($paths as $path) {
            File::makeDirectory(base_path($path), 0755, true, true);
            File::put(base_path($path.'/.gitkeep'), '');
        }
    }

    private function createRouteFiles(string $moduleBasePath): void
    {
        $routesPath = base_path("{$moduleBasePath}/routes");
        $routeName = $this->moduleNamePascalCase;

        File::put("{$routesPath}/api.php", $this->getRouteStub($routeName, 'API'));
        File::put("{$routesPath}/web.php", $this->getRouteStub($routeName, 'Web'));
        File::put("{$routesPath}/console.php", $this->getRouteStub($routeName, 'Console'));
    }

    private function createServiceProvider(string $moduleBasePath): void
    {
        $providerName = "{$this->moduleNamePascalCase}ServiceProvider";
        $providerPath = base_path("{$moduleBasePath}/src/Providers/{$providerName}.php");
        File::put($providerPath, $this->getServiceProviderStub($this->moduleNamePascalCase, $this->moduleNameKebabCase));
    }

    private function getRouteStub(string $moduleName, string $type): string
    {
        $lowerModuleName = Str::lower($moduleName);
        $commentType = Str::upper($type);
        $content = "<?php\n\ndeclare(strict_types=1);\n\nuse Illuminate\Support\Facades\Route;\n\n";
        $content .= "/*\n|--------------------------------------------------------------------------\n";
        $content .= "| {$commentType} Routes for {$moduleName} Module\n";
        $content .= "|--------------------------------------------------------------------------\n";
        $content .= "|\n";
        $content .= "| Here is where you can register {$lowerModuleName} {$type} routes for your application.\n";
        $content .= "|\n";
        $content .= "*/\n\n";

        if ($type === 'Web' || $type === 'API') {
            $prefix = ($type === 'API') ? "api/{$this->moduleNameKebabCase}" : $this->moduleNameKebabCase;
            $content .= "Route::prefix('{$prefix}')";
            $content .= "\n    // ->middleware(['web']) // Example for web routes\n";
            $content .= "    ->group(function () {\n";
            $content .= "    // Route::get('/', function () { return 'Hello from {$moduleName} {$type}!'; });\n";
            $content .= "});\n";
        } elseif ($type === 'Console') {
            $content .= "// Example: Artisan::command('{$this->moduleNameKebabCase}:inspire', function () { ... })->describe('...');\n";
        }

        return $content;
    }

    private function displayNextSteps(): void
    {
        note('Next steps to complete module setup:');

        $name = $this->moduleNamePascalCase;
        $this->info('Add the following lines to your composer.json file in the psr-4 section:');
        $this->info('    "Modules\\\\'.$name.'\\\\": "modules/'.$name.'/src",');
        $this->info('    "Modules\\\\'.$name.'\\\\Database\\\\Factories\\\\": "modules/'.$name.'/database/factories",');
        $this->info('    "Modules\\\\'.$name.'\\\\Database\\\\Seeders\\\\": "modules/'.$name.'/database/seeders",');
        $this->info('Then run "composer dump-autoload" to autoload the new module.');

        $this->info('Add the Service Provider to `bootstrap/providers.php` file:');
        $this->info('    Modules\\'.$name.'\\Providers\\'.$name.'ServiceProvider::class,');
    }
}
