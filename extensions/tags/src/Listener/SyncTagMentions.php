<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Listener;

use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
use Illuminate\Contracts\Events\Dispatcher;
use s9e\TextFormatter\Utils;

class SyncTagMentions
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen([Posted::class, Restored::class, Revised::class], [$this, 'syncTagMentions']);
        $events->listen([Deleted::class, Hidden::class], [$this, 'removeTagMentions']);
    }

    /**
     * @param Posted|Restored|Revised $event
     * @param array $mentioned
     * @return void
     */
    public function syncTagMentions($event): void
    {
        $content = $event->post->parsedContent;
        $mentioned = Utils::getAttributeValues($content, 'TAGMENTION', 'id');
        $event->post->mentionsTags()->sync($mentioned);
        $event->post->unsetRelation('mentionsTags');
    }

    /**
     * @param Deleted|Hidden $event
     * @return void
     */
    public function removeTagMentions($event)
    {
        $event->post->mentionsTags()->sync([]);
    }
}
