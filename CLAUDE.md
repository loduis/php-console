# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP console application framework built on top of Symfony Console components. The library provides a simplified way to create console applications with automatic command discovery and dependency injection capabilities.

## Development Commands

### Basic Operations
- `composer install` - Install dependencies
- `php tests/console.php list` - List all available console commands
- `php tests/console.php test:1` - Run the test command
- `php tests/console.php test:2` - Run the call test command (demonstrates command calling)

### Testing
No formal testing framework is configured. The project uses simple test commands in `tests/app/Commands/` for manual testing.

## Architecture

### Core Components

**Application Class** (`src/Application.php`)
- Extends Symfony Console Application
- Handles automatic command discovery from specified directories
- Provides dependency injection for command constructors
- Adds `--env` option support for environment-aware commands

**Command Base Class** (`src/Command.php`)
- Abstract base class extending Symfony Command
- Provides Laravel-style console command interface
- Key methods: `__invoke()` (must be implemented), `info()`, `line()`, `call()`
- Supports verbosity levels and command chaining

**Command Discovery System**
- `Resolve` class scans directories for `*Command.php` files
- `Source` class parses PHP files to extract class names and command properties
- Commands auto-register based on their `$name` property

### Command Structure

Commands must:
1. Extend `PhpConsole\Command`
2. Define a `$name` property (e.g., `'test:1'`)
3. Implement the `__invoke()` method
4. Optionally set `$description` and `$help` properties

Example command structure:
```php
class MyCommand extends Command
{
    protected $name = 'my:command';
    protected $description = 'Command description';
    
    public function __invoke(): int
    {
        $this->info('Hello World');
        return static::SUCCESS;
    }
}
```

### Directory Structure
- `src/` - Core framework classes
- `src/Command/` - Command discovery and parsing utilities
- `tests/app/Commands/` - Example/test commands
- `tests/console.php` - Test application entry point

### Dependencies
- Symfony Console (^6.0) - Core console functionality
- Symfony Dependency Injection (^6.0) - Service container
- Symfony Finder (^6.0) - File system operations

## Key Features

1. **Automatic Command Discovery** - Commands are auto-discovered from specified directories
2. **Dependency Injection** - Constructor parameters are automatically resolved
3. **Environment Support** - Built-in `--env` option for environment-specific behavior
4. **Command Chaining** - Commands can call other commands using `$this->call()`
5. **Laravel-style Interface** - Familiar API for Laravel developers