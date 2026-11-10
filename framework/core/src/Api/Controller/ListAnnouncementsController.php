<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Announcements\AnnouncementsFetcher;
use Flarum\Http\RequestUtil;
use Illuminate\Cache\Repository as CacheRepository;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ListAnnouncementsController implements RequestHandlerInterface
{
    public const CACHE_KEY = 'flarum.announcements';
    public const CACHE_FRESH_TTL = 1 * 24 * 3600;  // serve fresh for 1 day
    public const CACHE_STALE_TTL = 14 * 24 * 3600; // allow stale for 14 days
    public const CACHE_TTL = self::CACHE_STALE_TTL; // used by the console command

    public function __construct(
        protected CacheRepository $cache,
        protected AnnouncementsFetcher $fetcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        if ($request->getQueryParams()['bust'] ?? false) {
            try {
                $announcements = $this->fetcher->fetch();
                $this->cache->put(self::CACHE_KEY, $announcements, self::CACHE_STALE_TTL);
            } catch (\RuntimeException) {
                $announcements = $this->cache->get(self::CACHE_KEY, []);
            }

            return new JsonResponse($announcements);
        }

        $announcements = $this->cache->flexible(
            self::CACHE_KEY,
            [self::CACHE_FRESH_TTL, self::CACHE_STALE_TTL],
            function () {
                try {
                    return $this->fetcher->fetch();
                } catch (\RuntimeException) {
                    return null; // keep existing cached value
                }
            }
        ) ?? [];

        return new JsonResponse($announcements);
    }
}
