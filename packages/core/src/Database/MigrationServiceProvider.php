<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Foundation\AbstractServiceProvider;
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

        $this->app->bind(MigrationCreator::class, function () {
            return new MigrationCreator(
                $this->app->make(Filesystem::class),
                $this->app->basePath()
            );
        });
    }
}
