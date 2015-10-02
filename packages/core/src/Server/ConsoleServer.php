<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Server;

use Flarum\Core;
use Symfony\Component\Console\Application;

class ConsoleServer extends ServerAbstract
{
    /**
     * @return void
     */
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

        $console = new Application('Flarum', $app::VERSION);

        if (!Core::isInstalled()) {
            $app->register('Flarum\Install\InstallServiceProvider');
            $console->add($app->make('Flarum\Install\Console\InstallCommand'));
        }

        $console->add($app->make('Flarum\Console\UpgradeCommand'));
        $console->add($app->make('Flarum\Console\GenerateExtensionCommand'));
        $console->add($app->make('Flarum\Console\GenerateMigrationCommand'));

        return $console;
    }
}
