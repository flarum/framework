<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Console;

use Flarum\Database\Console\GenerateMigrationCommand;
use Flarum\Database\Console\MigrateCommand;
use Flarum\Database\Console\ResetCommand;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Console\CacheClearCommand;
use Flarum\Foundation\Console\InfoCommand;

class ConsoleServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('flarum.console.commands', function () {
            return [
                CacheClearCommand::class,
                GenerateMigrationCommand::class,
                InfoCommand::class,
                MigrateCommand::class,
                ResetCommand::class,
            ];
        });
    }
}
