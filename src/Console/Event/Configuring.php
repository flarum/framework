<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Console\Event;

use Flarum\Foundation\Application;
use Symfony\Component\Console\Application as ConsoleApplication;

/**
 * Configure the console application
 *
 * This event is fired after the core commands are added to the application
 */
class Configuring
{
    /**
     * @var Application
     */
    public $app;

    /**
     * @var ConsoleApplication
     */
    public $console;

    /**
     * @param Application $app
     * @param ConsoleApplication $console
     */
    public function __construct(Application $app, ConsoleApplication $console)
    {
        $this->app = $app;
        $this->console = $console;
    }
}
