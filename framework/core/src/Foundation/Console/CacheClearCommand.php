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

class CacheClearCommand extends AbstractCommand
{
    /**
     * @var Store
     */
    protected $cache;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @param Store $cache
     * @param Paths $paths
     */
    public function __construct(Store $cache, Dispatcher $events, Paths $paths)
    {
        $this->cache = $cache;
        $this->events = $events;
        $this->paths = $paths;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Remove all temporary and generated files');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $this->info('Clearing the cache...');

        $succeeded = $this->cache->flush();

        if (! $succeeded) {
            $this->error('Could not clear contents of `storage/cache`. Please adjust file permissions and try again. This can frequently be fixed by clearing cache via the `Tools` dropdown on the Administration Dashboard page.');

            return 1;
        }

        $storagePath = $this->paths->storage;
        array_map('unlink', glob($storagePath.'/formatter/*'));
        array_map('unlink', glob($storagePath.'/locale/*'));
        array_map('unlink', glob($storagePath.'/views/*'));

        $this->events->dispatch(new ClearingCache);
    }
}
