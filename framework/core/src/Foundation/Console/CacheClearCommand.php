<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Foundation\Paths;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Command\Command;

class CacheClearCommand extends AbstractCommand
{
    public function __construct(
        protected Store $cache,
        protected Dispatcher $events,
        protected Paths $paths
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Remove all temporary and generated files');
    }

    protected function fire(): int
    {
        $this->info('Clearing the cache...');

        $succeeded = $this->cache->flush();

        if (! $succeeded) {
            $this->error('Could not clear contents of `storage/cache`. Please adjust file permissions and try again. This can frequently be fixed by clearing cache via the `Tools` dropdown on the Administration Dashboard page.');

            return Command::FAILURE;
        }

        $storagePath = $this->paths->storage;
        array_map('unlink', glob($storagePath.'/formatter/*'));
        array_map('unlink', glob($storagePath.'/locale/*'));
        array_map('unlink', glob($storagePath.'/views/*'));

        $this->events->dispatch(new ClearingCache);

        return Command::SUCCESS;
    }
}
