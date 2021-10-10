<?php

namespace PhpConsole;

use ReflectionClass;
use Symfony\Component\Console\Application as SymfonyApplication;
use PhpConsole\Command\Resolve;
use InvalidArgumentException;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;

class Application extends SymfonyApplication
{
    const VERSION = '1.0';

    public function __construct(string $basePath, string $commandDir)
    {
        parent::__construct('PhpConsole', static::VERSION);
        $path = realpath($basePath) . DIRECTORY_SEPARATOR .$commandDir;
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
