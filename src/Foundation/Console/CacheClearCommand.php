<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Foundation\Console;

use Flarum\Admin\Frontend as AdminWebApp;
use Flarum\Console\AbstractCommand;
use Flarum\Forum\Frontend as ForumWebApp;
use Flarum\Foundation\Application;
use Illuminate\Contracts\Cache\Store;

class CacheClearCommand extends AbstractCommand
{
    /**
     * @var Store
     */
    protected $cache;

    /**
     * @var ForumWebApp
     */
    protected $forum;

    /**
     * @var AdminWebApp
     */
    protected $admin;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Store $cache
     * @param ForumWebApp $forum
     * @param AdminWebApp $admin
     * @param Application $app
     */
    public function __construct(Store $cache, ForumWebApp $forum, AdminWebApp $admin, Application $app)
    {
        $this->cache = $cache;
        $this->forum = $forum;
        $this->admin = $admin;
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

        $this->forum->getAssets()->flush();
        $this->admin->getAssets()->flush();

        $this->cache->flush();

        $storagePath = $this->app->storagePath();
        array_map('unlink', glob($storagePath.'/formatter/*'));
        array_map('unlink', glob($storagePath.'/locale/*'));
    }
}
