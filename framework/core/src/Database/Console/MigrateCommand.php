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
use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Builder;
use Symfony\Component\Console\Command\Command;

class MigrateCommand extends AbstractCommand
{
    public function __construct(
        protected Container $container,
        protected Paths $paths
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('migrate')
            ->setDescription('Run outstanding migrations');
    }

    protected function fire(): int
    {
        $this->info('Migrating Flarum...');

        $this->upgrade();

        $this->info('DONE.');

        return Command::SUCCESS;
    }

    public function upgrade(): void
    {
        $this->container->bind(Builder::class, function ($container) {
            return $container->make(ConnectionInterface::class)->getSchemaBuilder();
        });

        $migrator = $this->container->make(Migrator::class);
        $migrator->setOutput($this->output);

        $migrator->run(__DIR__.'/../../../migrations');

        $extensions = $this->container->make(ExtensionManager::class);
        $extensions->getMigrator()->setOutput($this->output);

        foreach ($extensions->getEnabledExtensions() as $name => $extension) {
            if ($extension->hasMigrations()) {
                $this->info('Migrating extension: '.$name);

                $extensions->migrate($extension);
            }
        }

        $this->container->make(SettingsRepositoryInterface::class)->set('version', Application::VERSION);
    }
}
