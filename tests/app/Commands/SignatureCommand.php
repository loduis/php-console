<?php

namespace PhpConsole\Tests\App\Commands;

use PhpConsole\Command;

class SignatureCommand extends Command
{
    protected $signature = 'user:create {name : The user name} {email? : The user email} {--force : Force the operation} {--role=user : The user role}';

    protected $description = 'Create a new user with signature syntax';

    public function __invoke(): int
    {
        $name = $this->argument('name');
        $email = $this->argument('email') ?: 'No email provided';
        $force = $this->option('force');
        $role = $this->option('role');

        $this->info("Creating user: {$name}");
        $this->comment("Email: {$email}");
        $this->comment("Role: {$role}");
        
        if ($force) {
            $this->warn('Force flag is enabled!');
        }

        $this->table(
            ['Property', 'Value'],
            [
                ['Name', $name],
                ['Email', $email],
                ['Role', $role],
                ['Forced', $force ? 'Yes' : 'No']
            ]
        );

        return static::SUCCESS;
    }
}