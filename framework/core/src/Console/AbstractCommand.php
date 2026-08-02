<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Console;

use Illuminate\Console\CommandMutex;
use Illuminate\Contracts\Console\Isolatable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;

    protected int $isolatedExitCode = Command::SUCCESS;

    public function __construct(?string $name = null)
    {
        parent::__construct($name);

        if ($this instanceof Isolatable) {
            $this->configureIsolation();
        }
    }

    protected function configureIsolation(): void
    {
        $this->getDefinition()->addOption(new InputOption(
            'isolated',
            null,
            InputOption::VALUE_OPTIONAL,
            'Do not run the command if another instance of the command is already running',
            false
        ));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $isolated = $this instanceof Isolatable && $input->getOption('isolated') !== false;

        if ($isolated && ! $this->commandIsolationMutex()->create($this)) {
            $this->comment(sprintf('The [%s] command is already running.', $this->getName()));

            return (int) (is_numeric($input->getOption('isolated'))
                ? $input->getOption('isolated')
                : $this->isolatedExitCode);
        }

        try {
            return $this->fire() ?: 0;
        } finally {
            if ($isolated) {
                $this->commandIsolationMutex()->forget($this);
            }
        }
    }

    protected function commandIsolationMutex(): CommandMutex
    {
        return resolve(CommandMutex::class);
    }

    abstract protected function fire(): int;

    protected function hasOption(string $name): bool
    {
        return $this->input->hasOption($name);
    }

    protected function info(string $message): void
    {
        $this->output->writeln("<info>$message</info>");
    }

    protected function comment(string $message): void
    {
        $this->output->writeln("<comment>$message</comment>");
    }

    /**
     * Send an error or warning message to the user.
     * If possible, this will send the message via STDERR.
     */
    protected function error(string $message): void
    {
        if ($this->output instanceof ConsoleOutputInterface) {
            $this->output->getErrorOutput()->writeln("<error>$message</error>");
        } else {
            $this->output->writeln("<error>$message</error>");
        }
    }
}
