<?php

namespace PhpConsole;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

abstract class Command extends SymfonyCommand
{
    /**
     * The input interface implementation.
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected InputInterface $input;

    /**
     * The output interface implementation.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected OutputInterface $output;

    /**
     * The default verbosity of output commands.
     *
     * @var int
     */
    protected $verbosity = OutputInterface::VERBOSITY_NORMAL;

    /**
     * The mapping between human readable verbosity levels and Symfony's OutputInterface.
     *
     * @var array
     */
    protected $verbosityMap = [
        'v' => OutputInterface::VERBOSITY_VERBOSE,
        'vv' => OutputInterface::VERBOSITY_VERY_VERBOSE,
        'vvv' => OutputInterface::VERBOSITY_DEBUG,
        'quiet' => OutputInterface::VERBOSITY_QUIET,
        'normal' => OutputInterface::VERBOSITY_NORMAL,
    ];

    protected $name;

    protected $signature;

    protected $description = 'Command description';

    protected $help;

    protected array $parsedArguments = [];

    protected array $parsedOptions = [];

    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Parse signature if provided
        if ($this->signature) {
            $this->parseSignature();
        }

        parent::__construct($this->name);

        // Once we have constructed the command, we'll set the description and other
        // related properties of the command. If a signature wasn't used to build
        // the command we'll set the arguments and the options on this command.
        $this->setDescription((string) $this->description);

        $this->setHelp((string) $this->help);
    }

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return (int) $this->__invoke($input, $output);
    }

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    abstract public function __invoke();

    /**
     * Write a string as information output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function info($string, $verbosity = null)
    {
        $this->line($string, 'info', $verbosity);
    }

    /**
     * Write a string as error output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function error($string, $verbosity = null)
    {
        $this->line($string, 'error', $verbosity);
    }

    /**
     * Write a string as warning output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function warn($string, $verbosity = null)
    {
        $this->line($string, 'fg=yellow', $verbosity);
    }

    /**
     * Write a string as comment output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function comment($string, $verbosity = null)
    {
        $this->line($string, 'comment', $verbosity);
    }

    /**
     * Display table with headers and rows.
     *
     * @param  array  $headers
     * @param  array  $rows
     * @return void
     */
    public function table(array $headers, array $rows)
    {
        $table = new \Symfony\Component\Console\Helper\Table($this->output);
        $table->setHeaders($headers)->setRows($rows);
        $table->render();
    }

    public function ask(string $question, ?string $default = null): ?string
    {
        $helper = $this->getHelper('question');
        $questionInstance = new \Symfony\Component\Console\Question\Question($question, $default);
        
        return $helper->ask($this->input, $this->output, $questionInstance);
    }

    public function confirm(string $question, bool $default = false): bool
    {
        $helper = $this->getHelper('question');
        $questionInstance = new \Symfony\Component\Console\Question\ConfirmationQuestion($question, $default);
        
        return $helper->ask($this->input, $this->output, $questionInstance);
    }

    public function choice(string $question, array $choices, string|int|null $default = null, ?int $attempts = null): string
    {
        $helper = $this->getHelper('question');
        $questionInstance = new \Symfony\Component\Console\Question\ChoiceQuestion($question, $choices, $default);
        
        if ($attempts) {
            $questionInstance->setMaxAttempts($attempts);
        }
        
        return $helper->ask($this->input, $this->output, $questionInstance);
    }

    public function newProgressBar(int $max = 0): \Symfony\Component\Console\Helper\ProgressBar
    {
        return new \Symfony\Component\Console\Helper\ProgressBar($this->output, $max);
    }

    /**
     * Write a string in a newline.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function newLine($string = '', $verbosity = null)
    {
        $this->output->writeln($string, $this->parseVerbosity($verbosity));
    }

    /**
     * Write a string as standard output.
     *
     * @param  string  $string
     * @param  string|null  $style
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function line($string, $style = null, $verbosity = null)
    {
        $styled = $style ? "<$style>$string</$style>" : $string;

        $this->output->writeln($styled, $this->parseVerbosity($verbosity));
    }

    /**
     * Get the verbosity level in terms of Symfony's OutputInterface level.
     *
     * @param  string|int|null  $level
     * @return int
     */
    protected function parseVerbosity($level = null)
    {
        if (isset($this->verbosityMap[$level])) {
            $level = $this->verbosityMap[$level];
        } elseif (! is_int($level)) {
            $level = $this->verbosity;
        }

        return $level;
    }

    /**
     * Initializes the command after the input has been bound and before the input
     * is validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @see InputInterface::bind()
     * @see InputInterface::validate()
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Run an Artisan console command by name.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return int
     *
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    protected function call(string $command, array $parameters = [])
    {
        $command = $this->getApplication()->find($command);

        $input = new ArrayInput($parameters);

        return $command->run($input, $this->output);
    }

    public function hasOption(string $name): bool
    {
        return $this->input->hasOption($name);
    }

    public function option(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->input->getOptions();
        }

        return $this->input->getOption($key);
    }

    public function hasArgument(string|int $name): bool
    {
        return $this->input->hasArgument($name);
    }

    public function argument(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->input->getArguments();
        }

        return $this->input->getArgument($key);
    }

    /**
     * Parse the signature to extract command name, arguments and options.
     *
     * @return void
     */
    protected function parseSignature(): void
    {
        if (!$this->signature) {
            return;
        }

        // Extract command name (everything before first space or {)
        preg_match('/^([^\s{]+)/', $this->signature, $nameMatch);
        if (isset($nameMatch[1])) {
            $this->name = $nameMatch[1];
        }

        // Extract arguments: {name}, {name?}, {name=default}, {name : description}
        preg_match_all('/\{([^}]+)\}/', $this->signature, $matches);
        
        foreach ($matches[1] as $match) {
            if (str_starts_with($match, '--')) {
                // This is an option
                $this->parseOption($match);
            } else {
                // This is an argument
                $this->parseArgument($match);
            }
        }
    }

    /**
     * Parse an argument from signature.
     *
     * @param string $argument
     * @return void
     */
    protected function parseArgument(string $argument): void
    {
        $name = $argument;
        $description = '';
        $optional = false;
        $default = null;

        // Extract description if present: {name : description}
        if (strpos($argument, ' : ') !== false) {
            [$name, $description] = explode(' : ', $argument, 2);
        }

        // Check if optional: {name?}
        if (str_ends_with($name, '?')) {
            $optional = true;
            $name = rtrim($name, '?');
        }

        // Check for default value: {name=default}
        if (strpos($name, '=') !== false) {
            [$name, $default] = explode('=', $name, 2);
            $optional = true;
        }

        $mode = $optional ? InputArgument::OPTIONAL : InputArgument::REQUIRED;

        $this->parsedArguments[] = new InputArgument($name, $mode, $description, $default);
    }

    /**
     * Parse an option from signature.
     *
     * @param string $option
     * @return void
     */
    protected function parseOption(string $option): void
    {
        $name = ltrim($option, '-');
        $description = '';
        $mode = InputOption::VALUE_NONE;
        $default = null;

        // Extract description if present: {--option : description}
        if (strpos($option, ' : ') !== false) {
            [$name, $description] = explode(' : ', $option, 2);
            $name = ltrim($name, '-');
        }

        // Check for value modes: {--option=} or {--option=default}
        if (strpos($name, '=') !== false) {
            [$name, $default] = explode('=', $name, 2);
            $mode = $default === '' ? InputOption::VALUE_REQUIRED : InputOption::VALUE_OPTIONAL;
            if ($default === '') {
                $default = null;
            }
        }

        $this->parsedOptions[] = new InputOption($name, null, $mode, $description, $default);
    }

    /**
     * Specify the arguments and options on the command.
     *
     * @return void
     */
    protected function configure(): void
    {
        // If signature was used, add parsed arguments and options
        if ($this->signature) {
            foreach ($this->parsedArguments as $argument) {
                $this->getDefinition()->addArgument($argument);
            }

            foreach ($this->parsedOptions as $option) {
                $this->getDefinition()->addOption($option);
            }
        } else {
            // Fallback to legacy method for backwards compatibility
            foreach ($this->getArguments() as $arguments) {
                if ($arguments instanceof InputArgument) {
                    $this->getDefinition()->addArgument($arguments);
                } else {
                    $this->addArgument(...array_values($arguments));
                }
            }

            foreach ($this->getOptions() as $options) {
                if ($options instanceof InputOption) {
                    $this->getDefinition()->addOption($options);
                } else {
                    $this->addOption(...array_values($options));
                }
            }
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}
