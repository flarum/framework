<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        return $this->fire() ?: 0;
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
