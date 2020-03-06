<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Listener;

use Flarum\Discussion\Event\Searching;
use Flarum\Event\ConfigureDiscussionGambits;
use Flarum\Tags\Gambit\TagGambit;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Events\Dispatcher;

class FilterDiscussionListByTags
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureDiscussionGambits::class, [$this, 'addTagGambit']);
        $events->listen(Searching::class, [$this, 'hideTagsFromDiscussionList']);
    }

    /**
     * @param ConfigureDiscussionGambits $event
     */
    public function addTagGambit(ConfigureDiscussionGambits $event)
    {
        $event->gambits->add(TagGambit::class);
    }

    /**
     * @param Searching $event
     */
    public function hideTagsFromDiscussionList(Searching $event)
    {
        $query = $event->search->getQuery();

        if (count($event->search->getActiveGambits()) > 0) {
            return;
        }

        $query->whereNotIn('discussions.id', function ($query) {
            return $query->select('discussion_id')
                ->from('discussion_tag')
                ->whereIn('tag_id', Tag::where('is_hidden', 1)->pluck('id'));
        });
    }
}
