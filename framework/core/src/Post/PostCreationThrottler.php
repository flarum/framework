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
use Illuminate\Http\Request;

class PostCreationThrottler
{
    public static int $timeout = 10;

    public function __invoke(Request $request): ?bool
    {
        if (! $request->routeIs('api.discussions.create', 'api.posts.create')) {
            return null;
        }

        $actor = RequestUtil::getActor($request);

        if ($actor->can('postWithoutThrottle')) {
            return false;
        }

        if (Post::where('user_id', $actor->id)->where('created_at', '>=', Carbon::now()->subSeconds(self::$timeout))->exists()) {
            return true;
        }

        return null;
    }
}
