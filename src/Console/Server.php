<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Console;

use Flarum\Console\Event\Configuring;
use Flarum\Foundation\Application;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Application as ConsoleApplication;

class Server
{
    protected $commands;

    public function __construct(array $commands)
    {
        $this->commands = $commands;
    }

    public function listen()
    {
        $console = new ConsoleApplication('Flarum', Application::VERSION);

        foreach ($this->commands as $command) {
            $console->add($command);
        }

        $this->extend($console);

        exit($console->run());
    }

    private function extend(ConsoleApplication $console)
    {
        $app = Application::getInstance();

        $events = $app->make(Dispatcher::class);
        $events->fire(new Configuring($app, $console));
    }
}
