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

use Flarum\Api\Serializer\PostSerializer;
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Mentions\Notification\UserMentionedBlueprint;
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use s9e\TextFormatter\Utils;

class UpdateUserMentionsMetadata
{
    /**
     * @var NotificationSyncer
     */
    protected $notifications;

    /**
     * @param NotificationSyncer $notifications
     */
    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureNotificationTypes::class, [$this, 'addNotificationType']);
        $events->listen(Posted::class, [$this, 'whenPosted']);
        $events->listen(Revised::class, [$this, 'whenRevised']);
        $events->listen(Hidden::class, [$this, 'whenHidden']);
        $events->listen(Restored::class, [$this, 'whenRestored']);
        $events->listen(Deleted::class, [$this, 'whenDeleted']);
    }

    /**
     * @param ConfigureNotificationTypes $event
     */
    public function addNotificationType(ConfigureNotificationTypes $event)
    {
        $event->add(UserMentionedBlueprint::class, PostSerializer::class, ['alert']);
    }

    /**
     * @param Posted $event
     */
    public function whenPosted(Posted $event)
    {
        $this->postBecameVisible($event->post);
    }

    /**
     * @param Revised $event
     */
    public function whenRevised(Revised $event)
    {
        $this->postBecameVisible($event->post);
    }

    /**
     * @param Hidden $event
     */
    public function whenHidden(Hidden $event)
    {
        $this->postBecameInvisible($event->post);
    }

    /**
     * @param Restored $event
     */
    public function whenRestored(Restored $event)
    {
        $this->postBecameVisible($event->post);
    }

    /**
     * @param Deleted $event
     */
    public function whenDeleted(Deleted $event)
    {
        $this->postBecameInvisible($event->post);
    }

    /**
     * @param Post $post
     */
    protected function postBecameVisible(Post $post)
    {
        $mentioned = Utils::getAttributeValues($post->parsedContent, 'USERMENTION', 'id');

        $this->sync($post, $mentioned);
    }

    /**
     * @param Post $post
     */
    protected function postBecameInvisible(Post $post)
    {
        $this->sync($post, []);
    }

    /**
     * @param Post $post
     * @param array $mentioned
     */
    protected function sync(Post $post, array $mentioned)
    {
        $post->mentionsUsers()->sync($mentioned);

        $users = User::whereIn('id', $mentioned)
            ->get()
            ->filter(function ($user) use ($post) {
                return $post->isVisibleTo($user) && $user->id !== $post->user->id;
            })
            ->all();

        $this->notifications->sync(new UserMentionedBlueprint($post), $users);
    }
}
