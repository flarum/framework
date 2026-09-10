<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post;

use Carbon\Carbon;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;

class PostCreationThrottler
{
    public static int $timeout = 10;

    public function __invoke(ServerRequestInterface $request): ?bool
    {
        // Editing a post re-parses its content — the same expensive work as
        // creating one (resolving @mentions, rendering) — so the edit routes
        // are throttled the same way.
        if (! in_array($request->getAttribute('routeName'), ['discussions.create', 'posts.create', 'posts.update'])) {
            return null;
        }

        $actor = RequestUtil::getActor($request);

        if ($actor->can('postWithoutThrottle')) {
            return false;
        }

        // A recent create OR a recent edit counts: both re-parse, and they
        // share one timeout window rather than giving an attacker a separate
        // budget for each.
        $since = Carbon::now()->subSeconds(self::$timeout);

        if (Post::where('user_id', $actor->id)
            ->where(fn ($query) => $query
                ->where('created_at', '>=', $since)
                ->orWhere('edited_at', '>=', $since))
            ->exists()) {
            return true;
        }

        return null;
    }
}
