<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Listener;

use Flarum\Event\ConfigurePostsQuery;
use Illuminate\Support\Arr;

class AddFilterByMentions
{
    public function handle(ConfigurePostsQuery $event)
    {
        if ($mentionedId = Arr::get($event->filter, 'mentioned')) {
            $event->query->join('post_mentions_user', 'posts.id', '=', 'post_mentions_user.post_id')
                ->where('post_mentions_user.mentions_user_id', '=', $mentionedId);
        }
    }
}
