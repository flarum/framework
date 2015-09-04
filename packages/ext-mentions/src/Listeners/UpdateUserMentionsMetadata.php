<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Mentions\Listeners;

use Flarum\Mentions\Notifications\UserMentionedBlueprint;
use Flarum\Core\Notifications\NotificationSyncer;
use Flarum\Events\RegisterNotificationTypes;
use Flarum\Events\PostWasPosted;
use Flarum\Events\PostWasRevised;
use Flarum\Events\PostWasHidden;
use Flarum\Events\PostWasRestored;
use Flarum\Events\PostWasDeleted;
use Flarum\Core\Posts\Post;
use Flarum\Core\Users\User;
use Illuminate\Contracts\Events\Dispatcher;
use s9e\TextFormatter\Utils;

class UpdateUserMentionsMetadata
{
    protected $notifications;

    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(RegisterNotificationTypes::class, [$this, 'registerNotificationType']);

        $events->listen(PostWasPosted::class, [$this, 'whenPostWasPosted']);
        $events->listen(PostWasRevised::class, [$this, 'whenPostWasRevised']);
        $events->listen(PostWasHidden::class, [$this, 'whenPostWasHidden']);
        $events->listen(PostWasRestored::class, [$this, 'whenPostWasRestored']);
        $events->listen(PostWasDeleted::class, [$this, 'whenPostWasDeleted']);
    }

    public function registerNotificationType(RegisterNotificationTypes $event)
    {
        $event->register(
            UserMentionedBlueprint::class,
            'Flarum\Api\Serializers\PostBasicSerializer',
            ['alert']
        );
    }

    public function whenPostWasPosted(PostWasPosted $event)
    {
        $this->postBecameVisible($event->post);
    }

    public function whenPostWasRevised(PostWasRevised $event)
    {
        $this->postBecameVisible($event->post);
    }

    public function whenPostWasHidden(PostWasHidden $event)
    {
        $this->postBecameInvisible($event->post);
    }

    public function whenPostWasRestored(PostWasRestored $event)
    {
        $this->postBecameVisible($event->post);
    }

    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $this->postBecameInvisible($event->post);
    }

    protected function postBecameVisible(Post $post)
    {
        $mentioned = Utils::getAttributeValues($post->parsedContent, 'USERMENTION', 'id');

        $this->sync($post, $mentioned);
    }

    protected function postBecameInvisible(Post $post)
    {
        $this->sync($post, []);
    }

    protected function sync(Post $post, array $mentioned)
    {
        $post->mentionsUsers()->sync($mentioned);

        $users = User::whereIn('id', $mentioned)
            ->get()
            ->filter(function ($user) use ($post) {
                return $user->id !== $post->user->id;
            })
            ->all();

        $this->notifications->sync(new UserMentionedBlueprint($post), $users);
    }
}
