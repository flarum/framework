<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Announcements\Console;

use Flarum\Announcements\AnnouncementsFetcher;
use Flarum\Api\Controller\ListAnnouncementsController;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class RefreshAnnouncementsCommand extends Command
{
    protected $signature = 'announcements:refresh';
    protected $description = 'Fetch and cache the latest announcements from discuss.flarum.org.';

    public function handle(CacheRepository $cache, AnnouncementsFetcher $fetcher)
    {
        $this->info('Fetching announcements from discuss.flarum.org...');

        try {
            $announcements = $fetcher->fetch();
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $cache->put(ListAnnouncementsController::CACHE_KEY, $announcements, ListAnnouncementsController::CACHE_TTL);

        $this->info('Cached '.count($announcements).' announcements.');

        return self::SUCCESS;
    }
}
