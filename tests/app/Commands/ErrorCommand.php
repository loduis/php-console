<?php

namespace PhpConsole\Tests\App\Commands;

use PhpConsole\Command;

class ErrorCommand extends Command
{
    protected $name = 'test:error';

    protected $help = 'Es un commando de prueba';

    protected $description = 'Este es un comando para probar';

    public function __invoke(): int
    {
        $this->error('This is an error message');

        return static::FAILURE;
    }
}
