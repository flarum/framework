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
use Flarum\Extension\Console\ExtensionDisableAllCommand;
use Flarum\Extension\Console\ExtensionDisableCommand;
use Flarum\Extension\Console\ExtensionEnableAllCommand;
use Flarum\Extension\Console\ExtensionEnableCommand;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Console\CacheClearCommand;

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
                ExtensionDisableAllCommand::class,
                ExtensionDisableCommand::class,
                ExtensionEnableAllCommand::class,
                ExtensionEnableCommand::class,
                GenerateMigrationCommand::class,
                MigrateCommand::class,
                ResetCommand::class,
            ];
        });
    }
}
