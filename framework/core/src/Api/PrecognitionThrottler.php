<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Cache\Repository as Cache;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Allows `$limit` precognitive requests per `$window` seconds per user, or per
 * IP address for guests. Rebind `flarum.api.precognition_throttler` to change it.
 */
class PrecognitionThrottler
{
    public function __construct(
        protected Cache $cache,
        protected int $limit = 60,
        protected int $window = 60
    ) {
    }

    /**
     * @return bool whether the request must be throttled
     */
    public function __invoke(Request $request): bool
    {
        $actor = RequestUtil::getActor($request);

        $key = 'flarum.api.precognition_throttle.'.($actor->isGuest()
            ? 'ip.'.sha1((string) $request->getAttribute('ipAddress'))
            : 'user.'.$actor->id);

        // Fixed window: only the first request sets the TTL.
        $this->cache->add($key, 0, $this->window);

        return (int) $this->cache->increment($key) > $this->limit;
    }
}
