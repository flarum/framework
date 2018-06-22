<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Application;

class MigrationServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('Flarum\Database\MigrationRepositoryInterface', function ($app) {
            return new DatabaseMigrationRepository($app['flarum.db'], 'migrations');
        });

        $this->app->bind(MigrationCreator::class, function (Application $app) {
            return new MigrationCreator($app->make('Illuminate\Filesystem\Filesystem'), $app->basePath());
        });
    }
}
