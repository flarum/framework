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
    public static $timeout = 10;

    /**
     * @return bool|void
     */
    public function __invoke(ServerRequestInterface $request)
    {
        if (! in_array($request->getAttribute('routeName'), ['discussions.create', 'posts.create'])) {
            return;
        }

        $actor = RequestUtil::getActor($request);

        if ($actor->can('postWithoutThrottle')) {
            return false;
        }

        if (Post::where('user_id', $actor->id)->where('created_at', '>=', Carbon::now()->subSeconds(self::$timeout))->exists()) {
            return true;
        }
    }
}
