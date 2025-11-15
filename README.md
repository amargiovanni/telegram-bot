![GitHub Tests Action Status](https://github.com/Oltrematica/template-laravel/actions/workflows/run-tests.yml/badge.svg)
![GitHub PhpStan Action Status](https://github.com/Oltrematica/template-laravel/actions/workflows/phpstan.yml/badge.svg)

# Template Laravel

Replace all occurrences of `template-laravel` with your repository name.

## Introduction

A comprehensive, ready-to-use template for developing web applications with Laravel 12.

## Features

### Technology Stack
- **Laravel 12**: The latest PHP framework for web development

### Pre-installed Packages

#### Monitoring and Debugging Tools

- **Laravel Pulse**: Real-time application performance monitoring
- **Laravel Telescope**: Powerful debugging tool (active only in local environment)
- **Scramble**: Automatic API documentation generation (accessible at /docs/api)

#### User Management and Security

- **oltrematica/laravel-rate-limiter**: Rate limiting for API endpoints
- **spatie/laravel-activitylog**: User activity logging (configured with LogsActivityAllDirty)

#### Development and Testing Tools

- **Pest**: Modern testing framework with pre-configured ArchTests
- **Larastan**: Static analysis for PHP code
- **Pint**: PHP code formatter
- **Rector**: Automated code refactoring tool

#### Default Configurations

The application's ServiceProvider includes the following configurations:
- Model strict mode
- Aggressive Vite prefetch
- Carbon immutable
- Model unguard
- Blocking destructive commands in production
- Forced HTTPS in production

#### New Module Creation

The template includes a custom Artisan command for rapid scaffolding of new application modules:

```bash
php artisan scaffold:new-module ModuleName
```

This command will automatically generate the basic structure for a new module, including:
- Model
- Migration
- Factory
- Filament Resource
- Basic tests
