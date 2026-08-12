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
use Flarum\Queue\QueueFactory;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Builder;
use Illuminate\Queue\Worker;
use Symfony\Component\Console\Command\Command;

class MigrateCommand extends AbstractCommand implements Isolatable
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
        // A migration reshapes the schema out from under any queue worker
        // running at the same time. Pause the queue for the duration so no job
        // runs against a half-changed database, and resume it the moment the
        // migrations finish — even if they fail, since a queue left paused
        // forever is its own outage.
        //
        // The pause is conditional: `migrate` is run routinely with nothing
        // pending, and idling every worker for a no-op run would be a
        // regression.
        if ($this->hasPendingMigrations()) {
            $this->pauseQueue();
        }

        try {
            $this->runMigrations();
        } finally {
            // The resume is not conditional. The web updater arms a pause the
            // moment it detects the version drift — before this command runs,
            // and whether or not there turns out to be anything to migrate. If
            // resume were gated on pending work too, a no-op run reached
            // through the updater would leave that pause set forever. Resuming
            // unconditionally clears whatever is set; on a queue that was never
            // paused it is a harmless no-op.
            $this->resumeQueue();
        }
    }

    /**
     * Pause every queue on the active connection for the duration of the
     * migration.
     *
     * This mirrors what `queue:pause --all` does — including its refusal to
     * act when pausing is disabled, since a flag no worker will read is only
     * misleading state — but without a console Application to `call()` into,
     * which Flarum's command base does not provide.
     */
    protected function pauseQueue(): void
    {
        if (! Worker::$pausable) {
            return;
        }

        $this->container->make(QueueFactory::class)->pause($this->queueConnectionName(), '*');
    }

    protected function resumeQueue(): void
    {
        // Resume is not gated on Worker::$pausable: a flag set earlier must
        // always be clearable, even if pausing was disabled since.
        $this->container->make(QueueFactory::class)->resume($this->queueConnectionName(), '*');
    }

    private function queueConnectionName(): string
    {
        return $this->container->make(Queue::class)->getConnectionName();
    }

    protected function runMigrations(): void
    {
        $this->container->bind(Builder::class, function ($container) {
            return $container->make(ConnectionInterface::class)->getSchemaBuilder();
        });

        $migrator = $this->container->make(Migrator::class);
        $migrator->setOutput($this->output);

        $migrator->run(__DIR__.'/../../../migrations');

        $extensions = $this->container->make(ExtensionManager::class);
        $extensions->getMigrator()->setOutput($this->output);

        // Re-sort and persist the enabled extension order so that any optional-dependencies
        // added or changed since the last enable/disable cycle take effect immediately.
        $extensions->syncExtensionOrder();

        foreach ($extensions->getEnabledExtensions() as $name => $extension) {
            if ($extension->hasMigrations()) {
                $this->info('Migrating extension: '.$name);

                $extensions->migrate($extension);
            }
        }

        $this->container->make(SettingsRepositoryInterface::class)->set('version', Application::VERSION);
    }

    /**
     * Is there any migration — core or extension — that has not run yet?
     *
     * A read-only comparison of the migration files on disk against the ledger
     * of what has run, with no side effects, so it is safe to ask before
     * deciding whether the run is worth pausing the queue for.
     */
    protected function hasPendingMigrations(): bool
    {
        $migrator = $this->container->make(Migrator::class);

        // Before the very first migration the ledger table does not exist yet.
        // Nothing has run, so everything on disk is pending.
        if (! $migrator->repositoryExists()) {
            return true;
        }

        if ($this->pathHasPendingMigrations($migrator, __DIR__.'/../../../migrations')) {
            return true;
        }

        $extensions = $this->container->make(ExtensionManager::class);

        foreach ($extensions->getEnabledExtensions() as $extension) {
            if (
                $extension->hasMigrations()
                && $this->pathHasPendingMigrations($migrator, $extension->getPath().'/migrations', $extension->getId())
            ) {
                return true;
            }
        }

        return false;
    }

    private function pathHasPendingMigrations(Migrator $migrator, string $path, ?string $extensionId = null): bool
    {
        $files = $migrator->getMigrationFiles($path);
        $ran = $migrator->getRepository()->getRan($extensionId);

        return count(array_diff($files, $ran)) > 0;
    }
}
