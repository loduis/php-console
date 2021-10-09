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

        $name = $source->getProperty('name');
        if (!$name) {
            return [];
        }
        $name = trim($name, '"\'');

        return [$name => $class];
    }
}
