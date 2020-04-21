<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Console\Event;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\Console\Application;

/**
 * @deprecated
 */
class Configuring
{
    /**
     * @var Container
     */
    public $app;

    /**
     * @var Application
     */
    public $console;

    /**
     * @param Container   $container
     * @param Application $console
     */
    public function __construct(Container $container, Application $console)
    {
        $this->app = $container;
        $this->console = $console;
    }

    /**
     * Add a console command to the flarum binary.
     *
     * @param Command|string $command
     */
    public function addCommand($command)
    {
        if (is_string($command)) {
            $command = $this->app->make($command);
        }

        if ($command instanceof Command) {
            $command->setLaravel($this->app);
        }

        $this->console->add($command);
    }
}
