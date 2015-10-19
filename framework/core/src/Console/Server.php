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

use Flarum\Foundation\AbstractServer;
use Symfony\Component\Console\Application;

class Server extends AbstractServer
{
    public function listen()
    {
        $console = $this->getConsoleApplication();

        exit($console->run());
    }

    /**
     * @return Application
     */
    protected function getConsoleApplication()
    {
        $app = $this->getApp();

        $console = new Application('Flarum', $app->version());

        $app->register('Flarum\Install\InstallServiceProvider');

        $console->add($app->make('Flarum\Install\Console\InstallCommand'));
        $console->add($app->make('Flarum\Update\Console\MigrateCommand'));
        $console->add($app->make('Flarum\Console\Command\GenerateExtensionCommand'));
        $console->add($app->make('Flarum\Console\Command\GenerateMigrationCommand'));

        return $console;
    }
}
