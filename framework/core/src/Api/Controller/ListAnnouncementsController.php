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
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ListAnnouncementsController implements RequestHandlerInterface
{
    public const CACHE_KEY = 'flarum.announcements';
    public const CACHE_TTL = 14 * 24 * 3600; // 14 days

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
                $this->cache->put(self::CACHE_KEY, $announcements, self::CACHE_TTL);
            } catch (\RuntimeException) {
                $announcements = $this->cache->get(self::CACHE_KEY, []);
            }

            return new JsonResponse($announcements);
        }

        $announcements = $this->cache->remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
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
