<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\integration;

use Flarum\Foundation\Application;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

abstract class ConsoleTestCase extends TestCase
{
    protected $console;

    protected function console()
    {
        if (is_null($this->console)) {
            $this->console = new ConsoleApplication('Flarum', Application::VERSION);
            $this->console->setAutoExit(false);

            foreach ($this->app()->getConsoleCommands() as $command) {
                $this->console->add($command);
            }
        }

        return $this->console;
    }

    protected function runCommand(array $inputArray)
    {
        $input = new ArrayInput($inputArray);
        $output = new BufferedOutput();

        $this->console()->run($input, $output);

        return trim($output->fetch());
    }
}
