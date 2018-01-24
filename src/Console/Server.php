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
use Flarum\Database\Console\GenerateMigrationCommand;
use Flarum\Database\Console\MigrateCommand;
use Flarum\Foundation\Application;
use Flarum\Foundation\Console\CacheClearCommand;
use Flarum\Foundation\Console\InfoCommand;
use Flarum\Foundation\Site;
use Flarum\Install\Console\InstallCommand;
use Flarum\Install\InstallServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Application as ConsoleApplication;

class Server
{
    /**
     * @param Site $site
     * @return Server
     */
    public static function fromSite(Site $site)
    {
        return new static($site->boot());
    }

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function listen()
    {
        $console = $this->getConsoleApplication();

        exit($console->run());
    }

    /**
     * @return ConsoleApplication
     */
    protected function getConsoleApplication()
    {
        $console = new ConsoleApplication('Flarum', $this->app->version());

        $this->app->register(InstallServiceProvider::class);

        $commands = [
            InstallCommand::class,
            MigrateCommand::class,
            GenerateMigrationCommand::class,
        ];

        if ($this->app->isInstalled()) {
            $commands = array_merge($commands, [
                InfoCommand::class,
                CacheClearCommand::class
            ]);
        }

        foreach ($commands as $command) {
            $console->add($this->app->make(
                $command,
                ['config' => $this->app->isInstalled() ? $this->app->make('flarum.config') : []]
            ));
        }

        $events = $this->app->make(Dispatcher::class);
        $events->fire(new Configuring($this->app, $console));

        return $console;
    }
}
