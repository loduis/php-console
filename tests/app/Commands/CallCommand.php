<?php

namespace PhpConsole\Tests\App\Commands;

use PhpConsole\Command;

class CallCommand extends Command
{
    protected $name = 'test:2';

    protected $help = 'Es un commando de prueba';

    protected $description = 'Este es un comando para probar';

    public function __invoke(): int
    {
        $this->call('test:1');

        return static::SUCCESS;
    }
}

