<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Foundation\Application;
use Flarum\Foundation\Event\ClearingCache;
use Illuminate\Contracts\Cache\Store;

class CacheClearCommand extends AbstractCommand
{
    /**
     * @var Store
     */
    protected $cache;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Store $cache
     * @param Application $app
     */
    public function __construct(Store $cache, Application $app)
    {
        $this->cache = $cache;
        $this->app = $app;

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

        $this->cache->flush();

        $storagePath = $this->app->storagePath();
        array_map('unlink', glob($storagePath.'/formatter/*'));
        array_map('unlink', glob($storagePath.'/locale/*'));

        event(new ClearingCache);
    }
}
