<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Application;
use Illuminate\Filesystem\Filesystem;

class MigrationServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton(MigrationRepositoryInterface::class, function ($app) {
            return new DatabaseMigrationRepository($app['flarum.db'], 'migrations');
        });

        $this->app->bind(MigrationCreator::class, function (Application $app) {
            return new MigrationCreator($app->make(Filesystem::class), $app->basePath());
        });
    }
}
