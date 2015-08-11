<?php namespace Flarum\Console;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends SymfonyCommand
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
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->fire();
    }

    /**
     * Fire the command.
     *
     * @return void
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
     * Send an info string to the user.
     *
     * @param string $string
     */
    protected function info($string)
    {
        $this->output->writeln("<info>$string</info>");
    }
}
