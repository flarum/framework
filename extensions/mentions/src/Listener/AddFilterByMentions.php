<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Mentions\Listener;

use Flarum\Event\ConfigurePostsQuery;

class AddFilterByMentions
{
    public function handle(ConfigurePostsQuery $event)
    {
        if ($mentionedId = array_get($event->filter, 'mentioned')) {
            $event->query->join('post_mentions_user', 'posts.id', '=', 'post_mentions_user.post_id')
                ->where('post_mentions_user.mentions_user_id', '=', $mentionedId);
        }
    }
}
