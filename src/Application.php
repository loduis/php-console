<?php

namespace PhpConsole;

use Closure;
use ReflectionClass;
use Symfony\Component\Console\Application as SymfonyApplication;
use PhpConsole\Command\Resolve;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;

class Application extends SymfonyApplication
{
    const VERSION = '1.0';

    public function __construct(string $commandDir, array $commandShared = [])
    {
        parent::__construct('PhpConsole', static::VERSION);
        $resolve = new Resolve($commandDir);
        $commands = [];
        foreach ($resolve->commands() + $commandShared as $command => $className) {
            $commands[$command] = function () use ($className) {
                return $this->newInstanceCommand($className);
            };
        }
        $commandLoader = new FactoryCommandLoader($commands);
        $this->setCommandLoader($commandLoader);
    }

    public static function create(string $commandDir, array $commandShared = [])
    {
        return new static($commandDir, $commandShared);
    }

    /**
     * Captures the value of the environment to be reestablished
     *
     * @param Closure $callback
     *
     * @return self
     */
    public function environment(Closure $callback): self
    {
        $args = $_SERVER['argv'] ?? [];
        foreach ($args as $i => $value) {
            if ($value === '--env') {
                $callback($args[$i + 1] ?? null);
                break;
            }

            if (strpos($value, '--env') === 0) {
                $value = array_slice(explode('=', $value), 1);
                $callback(reset($value));
                break;
            }
        }

        return $this;
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
            $paramType = $parameter->getType();
            if ($paramType === null) {
                throw new InvalidArgumentException("The parameter: '" . $parameter->name . "' there is not valid class");
            }
            $paramClass       = new ReflectionClass($paramType->getName());
            $value            = $paramClass->newInstance();
            $pos              = $parameter->getPosition();
            $parameters[$pos] = $value;
        }

        return $parameters;
    }

    /**
     * Gets the default input definition.
     *
     * @return InputDefinition An InputDefinition instance
     */
    protected function getDefaultInputDefinition()
    {
        $def = parent::getDefaultInputDefinition();
        $def->addOption(
            new InputOption(
                '--env',
                null,
                InputOption::VALUE_OPTIONAL,
                'The environment the command should run under'
            )
        );

        return $def;
    }
}
