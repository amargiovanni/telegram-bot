<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

it('creates a new module successfully with all directories and files', function (): void {
    $moduleName = 'TestModule';
    $baseModulePath = "modules/{$moduleName}";

    // 1. Mocks the check of existence of module directory
    File::shouldReceive('isDirectory')
        ->once()
        ->with(base_path($baseModulePath))
        ->andReturn(false); // Simula che il modulo non esista

    // 2. Mocks the creation of directories and files of the module
    $expectedDirectories = [
        "{$baseModulePath}/src/Http/Controllers",
        "{$baseModulePath}/src/Http/Middleware",
        "{$baseModulePath}/src/Models",
        "{$baseModulePath}/src/Providers",
        "{$baseModulePath}/src/Console/Commands",
        "{$baseModulePath}/database/migrations",
        "{$baseModulePath}/database/seeders",
        "{$baseModulePath}/database/factories",
        "{$baseModulePath}/routes",
        "{$baseModulePath}/resources/views",
        "{$baseModulePath}/resources/lang",
        "{$baseModulePath}/tests/Feature",
        "{$baseModulePath}/tests/Unit",
    ];

    foreach ($expectedDirectories as $path) {
        File::shouldReceive('makeDirectory')
            ->once()
            ->with(base_path($path), 0755, true, true)
            ->andReturn(true);
        File::shouldReceive('put')
            ->once()
            ->with(base_path($path.'/.gitkeep'), '');
    }

    // 3. Mocks the creation of the route files
    File::shouldReceive('put')
        ->once()
        ->with(base_path("{$baseModulePath}/routes/api.php"),
            Mockery::type('string')) // Verifica che venga scritto qualcosa
        ->andReturn(true); // o il numero di byte scritti

    File::shouldReceive('put')
        ->once()
        ->with(base_path("{$baseModulePath}/routes/web.php"), Mockery::type('string'))
        ->andReturn(true);

    File::shouldReceive('put')
        ->once()
        ->with(base_path("{$baseModulePath}/routes/console.php"), Mockery::type('string'))
        ->andReturn(true);

    // 4. Mocks the creation of the service provider
    File::shouldReceive('put')
        ->once()
        ->with(base_path("{$baseModulePath}/src/Providers/{$moduleName}ServiceProvider.php"), Mockery::type('string'))
        ->andReturn(true);

    // 5. Execute the command and asserts the output
    $this->artisan("scaffold:new-module {$moduleName}")
        ->expectsOutputToContain("Module {$moduleName} created successfully at {$baseModulePath}.")
        ->expectsOutputToContain('Next steps to complete module setup:')
        ->expectsOutputToContain('Add the following lines to your composer.json file in the psr-4 section:')
        ->expectsOutputToContain("    \"Modules\\\\{$moduleName}\\\\\": \"modules/{$moduleName}/src\",")
        ->expectsOutputToContain("    \"Modules\\\\{$moduleName}\\\\Database\\\\Factories\\\\\": \"modules/{$moduleName}/database/factories\",")
        ->expectsOutputToContain("    \"Modules\\\\{$moduleName}\\\\Database\\\\Seeders\\\\\": \"modules/{$moduleName}/database/seeders\",")
        ->expectsOutputToContain('Then run "composer dump-autoload" to autoload the new module.')
        ->expectsOutputToContain('Add the Service Provider to `bootstrap/providers.php` file:')
        ->expectsOutputToContain("    Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider::class,")
        ->assertExitCode(SymfonyCommand::SUCCESS);
});

it('fails if the module already exists', function (): void {
    $moduleName = 'ExistingModule';
    $baseModulePath = "modules/{$moduleName}";

    // 1. Mocks the check of existence of module directory (the module already exists)
    File::shouldReceive('isDirectory')
        ->once()
        ->with(base_path($baseModulePath))
        ->andReturn(true); // Simula che il modulo esista già

    // 2. Executes the command and asserts
    $this->artisan("scaffold:new-module {$moduleName}")
        // 3. Asserire l'output di avviso
        ->expectsOutputToContain("Module {$moduleName} already exists at {$baseModulePath}.")
        // 4. Asserire il codice di uscita corretto
        ->assertExitCode(SymfonyCommand::FAILURE);

    // 3. Asserts no directory creation or file writing
    File::shouldNotHaveReceived('makeDirectory');
    File::shouldNotHaveReceived('put');
});

it('correctly converts module name casing', function (): void {
    $inputName = 'user-management';
    $expectedPascalCase = 'UserManagement';
    $baseModulePath = "modules/{$expectedPascalCase}";

    File::shouldReceive('isDirectory')->once()->with(base_path($baseModulePath))->andReturn(false);
    // Mocks all other directory creation
    File::shouldReceive('makeDirectory')->andReturn(true);
    File::shouldReceive('put')->andReturn(true);

    $this->artisan("scaffold:new-module {$inputName}")
        ->expectsOutputToContain("Module {$expectedPascalCase} created successfully at {$baseModulePath}.")
        ->expectsOutputToContain("    \"Modules\\\\{$expectedPascalCase}\\\\\": \"modules/{$expectedPascalCase}/src\",")
        ->assertExitCode(SymfonyCommand::SUCCESS);

});
