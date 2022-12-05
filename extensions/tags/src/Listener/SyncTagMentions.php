<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Listener;

use Flarum\Extension\ExtensionManager;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
use Illuminate\Contracts\Events\Dispatcher;
use s9e\TextFormatter\Utils;

class SyncTagMentions
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @param NotificationSyncer $notifications
     */
    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

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
    public function syncTagMentions($event, array $mentioned): void
    {
        if ($this->extensions->isEnabled('flarum-mentions')) {
            $content = $event->post->parsedContent;
            $mentioned = Utils::getAttributeValues($content, 'TAGMENTION', 'id');
            $event->post->mentionsTags()->sync($mentioned);
            $event->post->unsetRelation('mentionsTags');
        }
    }

    /**
     * @param Deleted|Hidden $event
     * @return void
     */
    public function removeTagMentions($event)
    {
        if ($this->extensions->isEnabled('flarum-mentions')) {
            $event->post->mentionsTags()->sync([]);
        }
    }
}
