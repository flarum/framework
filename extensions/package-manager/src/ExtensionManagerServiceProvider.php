<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager;

use Flarum\ExtensionManager\Host\StorageDir;
use Flarum\ExtensionManager\Run\Drivers;
use Flarum\ExtensionManager\Run\RunStore;
use Flarum\ExtensionManager\Run\StepRunner;
use Flarum\ExtensionManager\Run\StepsFactory;
use Flarum\ExtensionManager\Work\ComposerStepsFactory;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Config;
use Flarum\Foundation\Paths;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\Factory as QueueFactory;

class ExtensionManagerServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        /*
         * A CLI update asking the web server to drop its compiled code; see
         * Opcache::requestWebReset(). bootstrap/web-reset.php takes it before any
         * extension boots; this covers a site whose storage is not where that
         * file looks, and is not a second stat everywhere else.
         */
        if (PHP_SAPI !== 'cli') {
            $storage = $this->container->make(Paths::class)->storage;

            if (! defined('FLARUM_EXTENSION_MANAGER_EARLY_STORAGE') || $storage !== FLARUM_EXTENSION_MANAGER_EARLY_STORAGE) {
                \Flarum\ExtensionManager\Host\Opcache::honourWebReset($storage);
            }
        }

        $this->container->singleton(RunStore::class, function ($container) {
            /*
             * Under storage/, which is already writable, already excluded from
             * the web root, and already backed up with the rest of the site. A
             * run's state is not secret but it is not public either.
             */
            return new RunStore(StorageDir::of($container->make(Paths::class)->storage).'/runs');
        });

        $this->container->singleton(Drivers::class, function ($container) {
            return new Drivers(
                $container->make(QueueFactory::class),
                $container->make(Dispatcher::class)
            );
        });

        /*
         * 🚨 Bound as a FACTORY, never as the work for "the current run".
         *
         * The obvious binding — ask the store for the latest run and build the
         * work around it — is correct under PHP-FPM and wrong in a queue worker,
         * which is a long-lived process where a singleton outlives the job that
         * created it. That worker would carry the finished run's staging
         * directory and journal into the next run, and a journal that disagrees
         * with what happened is a rollback that restores the wrong files.
         *
         * Handing the id in at step() time removes the question entirely.
         */
        $this->container->singleton(StepsFactory::class, function ($container) {
            /*
             * 🚨 Config is REQUIRED here, and this line is why.
             *
             * It used to be optional with a null default and this binding did
             * not pass it — so `siteUrl()` returned '' on every install, and
             * every run reported "No site address is configured, so this update
             * will not be judged by whether the site answers." The health check
             * and the automatic rollback-when-the-site-breaks were therefore
             * never once exercised, on any forum, since the day they were
             * written. Nothing errored; the feature simply was not there.
             */
            return new ComposerStepsFactory(
                $container->make(Paths::class),
                $container->make(Config::class)
            );
        });

        /*
         * 🚨 The lock directory is what stops two drivers doing the same item.
         * On a forum with a queue, the admin page polling and a worker WILL run
         * at once — without this they both read the same index and a package is
         * applied twice.
         */
        $this->container->singleton(StepRunner::class, function ($container) {
            $storage = $container->make(Paths::class)->storage;

            return new StepRunner(
                $container->make(RunStore::class),
                $container->make(StepsFactory::class),
                fn () => time(),
                StorageDir::of($storage).'/locks'
            );
        });
    }
}
