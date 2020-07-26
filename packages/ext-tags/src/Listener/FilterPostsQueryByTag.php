<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Listener;

use Flarum\Event\ConfigurePostsQuery;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class FilterPostsQueryByTag
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigurePostsQuery::class, [$this, 'filterQuery']);
    }

    /**
     * @param ConfigurePostsQuery $event
     */
    public function filterQuery(ConfigurePostsQuery $event)
    {
        if ($tagId = Arr::get($event->filter, 'tag')) {
            $event->query
                ->join('discussion_tag', 'discussion_tag.discussion_id', '=', 'posts.discussion_id')
                ->where('discussion_tag.tag_id', $tagId);
        }
    }
}
