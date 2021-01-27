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
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        return $this->fire() ?: 0;
    }

    /**
     * Fire the command.
     */
    abstract protected function fire();

    /**
     * Did the user pass the given option?
     *
     * @param string $name
     * @return bool
     */
    protected function hasOption($name)
    {
        return $this->input->hasOption($name);
    }

    /**
     * Send an info message to the user.
     *
     * @param string $message
     */
    protected function info($message)
    {
        $this->output->writeln("<info>$message</info>");
    }

    /**
     * Send an error or warning message to the user.
     *
     * If possible, this will send the message via STDERR.
     *
     * @param string $message
     */
    protected function error($message)
    {
        if ($this->output instanceof ConsoleOutputInterface) {
            $this->output->getErrorOutput()->writeln("<error>$message</error>");
        } else {
            $this->output->writeln("<error>$message</error>");
        }
    }
}
