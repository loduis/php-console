<?php

namespace PhpConsole\Tests\App\Commands;

use PhpConsole\Command;

class TestCommand extends Command
{
    protected $name = 'test:1';

    protected $help = 'Es un commando de prueba';

    protected $description = 'Este es un comando para probar';

    public function __invoke(): int
    {
        $this->info('Esto es una prueba');

        return static::SUCCESS;
    }
}
