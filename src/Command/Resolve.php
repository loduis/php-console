<?php

namespace PhpConsole\Command;

use Symfony\Component\Finder\Finder;

class Resolve
{
    private $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function commands(): array
    {
        $commands = [];
        if (is_dir($this->path)) {
            $files = Finder::create()
                ->in($this->path)
                ->name('*Command.php');
            foreach ($files as $file) {
                if ($command = $this->command($file)) {
                    $commands = array_merge($commands, $command);
                }
            }
        }

        return $commands;
    }

    protected function command($file): array
    {
        $source = new Source($file);
        $class = $source->getClassName();

        if (!$class) {
            return [];
        }

        // First try to get name from $name property
        $name = $source->getProperty('name');

        // If no $name property, try to extract from $signature property
        if (!$name) {
            $signature = $source->getProperty('signature');
            if ($signature) {
                $signature = trim($signature, '"\'');
                // Extract command name from signature (everything before first space or {)
                if (preg_match('/^([^\s{]+)/', $signature, $matches)) {
                    $name = $matches[1];
                }
            }
        }

        if (!$name) {
            return [];
        }

        $name = trim($name, '"\'');

        return [$name => $class];
    }
}
