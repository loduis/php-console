<?php

namespace PhpConsole\Tests\App\Commands;

use PhpConsole\Command;

class SimpleSignatureCommand extends Command
{
    protected $signature = 'simple:test {name}';
    protected $description = 'Simple signature test';

    public function __invoke(): int
    {
        $name = $this->argument('name');
        $this->info("Hello {$name}!");
        return static::SUCCESS;
    }
}