<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Database\Migrator;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Application;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Builder;

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
        $this->app->bind(Builder::class, function ($app) {
            return $app->make(ConnectionInterface::class)->getSchemaBuilder();
        });

        $migrator = $this->app->make(Migrator::class);
        $migrator->setOutput($this->output);

        $migrator->run(__DIR__.'/../../../migrations');

        $extensions = $this->app->make(ExtensionManager::class);
        $extensions->getMigrator()->setOutput($this->output);

        foreach ($extensions->getEnabledExtensions() as $name => $extension) {
            if ($extension->hasMigrations()) {
                $this->info('Migrating extension: '.$name);

                $extensions->migrate($extension);
            }
        }

        $this->app->make(SettingsRepositoryInterface::class)->set('version', $this->app->version());

        $this->info('Publishing assets...');

        $this->app->make('files')->copyDirectory(
            $this->app->vendorPath().'/components/font-awesome/webfonts',
            $this->app->publicPath().'/assets/fonts'
        );
    }
}
