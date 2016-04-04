<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Debug\Console;

use Flarum\Admin\Controller\ClientController as AdminClient;
use Flarum\Console\Command\AbstractCommand;
use Flarum\Forum\Controller\ClientController as ForumClient;
use Illuminate\Contracts\Cache\Store;

class CacheClearCommand extends AbstractCommand
{
    /**
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $cache;

    /**
     * @var \Flarum\Forum\Controller\ClientController
     */
    protected $forum;

    /**
     * @var \Flarum\Admin\Controller\ClientController
     */
    protected $admin;

    public function __construct(Store $cache, ForumClient $forum, AdminClient $admin)
    {
        $this->cache = $cache;
        $this->forum = $forum;
        $this->admin = $admin;

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

        $this->forum->flushAssets();
        $this->admin->flushAssets();

        $this->cache->flush();
    }
}
