<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Database\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Foundation\Application;

class MigrateCommand extends AbstractCommand
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->app = $application;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('migrate')
            ->setDescription('Run outstanding migrations');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $this->info('Migrating Flarum...');

        $this->upgrade();

        $this->info('DONE.');
    }

    public function upgrade()
    {
        $this->app->bind('Illuminate\Database\Schema\Builder', function ($app) {
            return $app->make('Illuminate\Database\ConnectionInterface')->getSchemaBuilder();
        });

        $migrator = $this->app->make('Flarum\Database\Migrator');
        $migrator->setOutput($this->output);

        $migrator->run(__DIR__.'/../../../migrations');

        $extensions = $this->app->make('Flarum\Extension\ExtensionManager');
        $extensions->getMigrator()->setOutput($this->output);

        foreach ($extensions->getEnabledExtensions() as $name => $extension) {
            if ($extension->hasMigrations()) {
                $this->info('Migrating extension: '.$name);

                $extensions->migrate($extension);
            }
        }

        $this->app->make('Flarum\Settings\SettingsRepositoryInterface')->set('version', $this->app->version());

        $this->info('Publishing assets...');

        $this->app->make('files')->copyDirectory(
            $this->app->basePath().'/vendor/components/font-awesome/webfonts',
            $this->app->publicPath().'/assets/fonts'
        );
    }
}
