<?php

namespace PhpConsole;

use ReflectionClass;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputOption;
use PhpConsole\Command\Resolve;
use InvalidArgumentException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;

class Application extends SymfonyApplication
{
    const VERSION = '1.0';

    protected $defaultCommands = [];

    private string $path;

    public function __construct(string $basePath, string $commandDir)
    {
        parent::__construct('PhpConsole', static::VERSION);

        $path = realpath($basePath) . DIRECTORY_SEPARATOR;
        try {
            $in = new ArgvInput(null, $this->getDefaultInputDefinition());
            $path .= $in->getOption('app') . DIRECTORY_SEPARATOR;
        } catch (\Throwable $e) {}
        $this->path = $path;
        $path .= $commandDir;
        $resolve = new Resolve($path);
        $commands = [];
        foreach ($resolve->commands() as $command => $className) {
            $commands[$command] = function () use ($className) {
                return $this->newInstanceCommand($className);
            };
        }
        $commandLoader = new FactoryCommandLoader($commands);
        $this->setCommandLoader($commandLoader);
    }

    public static function create(string $basePath, string $commandDir = 'Commands')
    {
        return new static($basePath, $commandDir);
    }

    /**
     * Base path where run de application
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Gets the default commands that should always be available.
     *
     * @return Command[] An array of default Command instances
     */
    protected function getDefaultCommands()
    {
        return array_merge(parent::getDefaultCommands(), $this->defaultCommands);
    }

    /**
     * Gets the default input definition.
     *
     * @return InputDefinition An InputDefinition instance
     */
    protected function getDefaultInputDefinition(): InputDefinition
    {
        $definition = parent::getDefaultInputDefinition();
        $option = new InputOption(
            '--app',
            null,
            InputOption::VALUE_REQUIRED,
            'The application directory the command should run under',
            'app'
        );
        $definition->addOption($option);

        return $definition;
    }

    /**
     * Create one instance for command class
     *
     * @param  string $commandClass
     *
     * @return \Illuminate\Console\Command
     */
    protected function newInstanceCommand($commandClass)
    {
        $class = new ReflectionClass($commandClass);

        return $class->newInstanceArgs($this->resolveCommandParameters($class));
    }

    /**
     * Resolve the parameters in the constructor
     *
     * @param  ReflectionClass $class
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function resolveCommandParameters(ReflectionClass $class)
    {
        $parameters = [];
        foreach ($class->getConstructor()->getParameters() as $parameter) {
            $paramClass = $parameter->getClass();
            if ($paramClass === null) {
                throw new InvalidArgumentException("The parameter: '" . $parameter->name . "' there is not valid class");
            }
            $value            = $paramClass->newInstance();
            $pos              = $parameter->getPosition();
            $parameters[$pos] = $value;
        }

        return $parameters;
    }
}
