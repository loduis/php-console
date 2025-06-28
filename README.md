# PHP Console Framework

A lightweight PHP console application framework built on top of Symfony Console components, providing automatic command discovery and Laravel-style command interface.

## Features

- 🔍 **Automatic Command Discovery** - Commands are auto-discovered from specified directories
- 💉 **Dependency Injection** - Constructor parameters are automatically resolved
- 🌍 **Environment Support** - Built-in `--env` option for environment-specific behavior
- 🔗 **Command Chaining** - Commands can call other commands using `$this->call()`
- 🎨 **Laravel-style Interface** - Familiar API for Laravel developers
- 📊 **Rich Output Methods** - Comprehensive output formatting and user interaction methods

## Requirements

- PHP >= 8.0.2
- Symfony Console ^6.0
- Symfony Dependency Injection ^6.0
- Symfony Finder ^6.0

## Installation

```bash
composer require loduis/php-console
```

## Quick Start

### 1. Create Your Console Application

```php
<?php
// console.php

use PhpConsole\Application;

require __DIR__ . '/vendor/autoload.php';

// Create application with command directory
Application::create(__DIR__ . '/Commands')->run();
```

### 2. Create Your First Command

```php
<?php
// Commands/GreetCommand.php

namespace App\Commands;

use PhpConsole\Command;

class GreetCommand extends Command
{
    protected $name = 'greet';
    protected $description = 'Greet a user';
    protected $help = 'This command greets a user with a personalized message';

    public function __invoke(): int
    {
        $name = $this->ask('What is your name?', 'World');
        
        $this->info("Hello, {$name}!");
        $this->comment('Welcome to PHP Console Framework');
        
        return static::SUCCESS;
    }
}
```

### 3. Run Your Command

```bash
php console.php greet
```

## Command Structure

All commands must:

1. Extend `PhpConsole\Command`
2. Define a `$name` property (command name)
3. Implement the `__invoke()` method
4. Optionally set `$description` and `$help` properties

```php
class MyCommand extends Command
{
    protected $name = 'my:command';
    protected $description = 'Command description';
    protected $help = 'Detailed help text';
    
    public function __invoke(): int
    {
        // Your command logic here
        return static::SUCCESS;
    }
}
```

## Available Output Methods

### Basic Output

```php
$this->info('Information message');        // Green text
$this->error('Error message');             // Red text  
$this->warn('Warning message');            // Yellow text
$this->comment('Comment message');         // Gray text
$this->line('Regular message');            // Normal text
$this->newLine();                          // Empty line
```

### User Input

```php
// Ask for input
$name = $this->ask('What is your name?', 'default');

// Confirmation
$confirmed = $this->confirm('Are you sure?', false);

// Multiple choice
$choice = $this->choice('Select option:', ['option1', 'option2'], 0);
```

### Tables

```php
$headers = ['Name', 'Email', 'Role'];
$rows = [
    ['John Doe', 'john@example.com', 'Admin'],
    ['Jane Smith', 'jane@example.com', 'User']
];

$this->table($headers, $rows);
```

### Progress Bars

```php
$progress = $this->newProgressBar(100);
$progress->start();

for ($i = 0; $i < 100; $i++) {
    // Do some work
    $progress->advance();
}

$progress->finish();
```

## Advanced Features

### Environment Support

Commands can access environment-specific configuration:

```php
// Run with environment
php console.php my:command --env=production

// Access in command
$env = $this->option('env');
```

### Command Chaining

Call other commands from within your command:

```php
public function __invoke(): int
{
    $this->info('Running setup...');
    
    // Call another command
    $this->call('cache:clear');
    $this->call('migrate', ['--force' => true]);
    
    return static::SUCCESS;
}
```

### Dependency Injection

Constructor dependencies are automatically resolved:

```php
class DatabaseCommand extends Command
{
    public function __construct(DatabaseConnection $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
        parent::__construct();
    }
}
```

## Command Arguments and Options

Define command arguments and options:

```php
protected function getArguments()
{
    return [
        ['name', InputArgument::REQUIRED, 'User name'],
        ['email', InputArgument::OPTIONAL, 'User email']
    ];
}

protected function getOptions()
{
    return [
        ['force', 'f', InputOption::VALUE_NONE, 'Force execution'],
        ['limit', 'l', InputOption::VALUE_REQUIRED, 'Limit results', 10]
    ];
}

public function __invoke(): int
{
    $name = $this->argument('name');
    $email = $this->argument('email');
    $force = $this->option('force');
    $limit = $this->option('limit');
}
```

## Testing

Test your console application:

```php
php console.php list                    # List all commands
php console.php help my:command         # Show command help
php console.php my:command --help       # Alternative help syntax
```

## Project Structure

```
your-project/
├── Commands/           # Your command classes
│   ├── GreetCommand.php
│   └── DatabaseCommand.php
├── console.php         # Application entry point
├── composer.json
└── vendor/
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Changelog

- **v6.0** - Updated to Symfony Console 6.0, added Laravel-style methods
- **v5.4** - Previous stable version

## Support

For support and questions, please open an issue on the [GitHub repository](https://github.com/loduis/php-console).