<?php

namespace PhpConsole;

use Closure;
use ReflectionClass;
use Symfony\Component\Console\Application as SymfonyApplication;
use PhpConsole\Command\Resolve;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
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
            $commands[$command] = fn() => $this->newInstanceCommand($className);
        }
        $commandLoader = new FactoryCommandLoader($commands);
        $this->setCommandLoader($commandLoader);
    }

    public static function create(string $commandDir, array $commandShared = []): static
    {
        return new static($commandDir, $commandShared);
    }

    public function environment(Closure $callback): self
    {
        $args = $_SERVER['argv'] ?? [];
        foreach ($args as $i => $value) {
            if ($value === '--env') {
                $callback($args[$i + 1] ?? null);
                break;
            }

            if (str_starts_with($value, '--env')) {
                $value = array_slice(explode('=', $value), 1);
                $callback(reset($value));
                break;
            }
        }

        return $this;
    }

    protected function newInstanceCommand(string $commandClass): object
    {
        $class = new ReflectionClass($commandClass);

        return $class->newInstanceArgs($this->resolveCommandParameters($class));
    }

    protected function resolveCommandParameters(ReflectionClass $class): array
    {
        $parameters = [];
        $constructor = $class->getConstructor();
        if (!$constructor) {
            return [];
        }
        
        foreach ($constructor->getParameters() as $parameter) {
            $paramType = $parameter->getType();
            if (!$paramType || !$paramType instanceof \ReflectionNamedType) {
                throw new InvalidArgumentException("The parameter: '{$parameter->name}' is not a valid class");
            }
            $paramClass = new ReflectionClass($paramType->getName());
            $value = $paramClass->newInstance();
            $parameters[$parameter->getPosition()] = $value;
        }

        return $parameters;
    }

    protected function getDefaultInputDefinition(): InputDefinition
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
