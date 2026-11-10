<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Job;

use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Post;
use Flarum\Queue\AbstractJob;
use Flarum\Subscriptions\Notification\NewPostBlueprint;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class SendReplyNotification extends AbstractJob
{
    public function __construct(
        protected Post $post,
        protected ?int $lastPostNumber
    ) {
        parent::__construct();
    }

    public function handle(NotificationSyncer $notifications): void
    {
        $post = $this->post;
        $discussion = $post->discussion;

        $usersToNotify = [];

        $followers = $discussion->readers()
            ->select('users.id', 'users.preferences', 'discussion_user.last_read_post_number')
            ->where('users.id', '!=', $post->user_id)
            ->where('discussion_user.subscription', 'follow');

        $followers->chunk(150, function (Collection $followers) use (&$usersToNotify) {
            $allUnreadUsers = [];
            $firstUnreadUsers = [];

            /**
             * @var \Flarum\User\User $user
             */
            foreach ($followers as $user) {
                $notifyForAll = $user->getPreference('flarum-subscriptions.notify_for_all_posts', false);

                if ($notifyForAll) {
                    $allUnreadUsers[] = $user;
                }
                // Only notify if this is the next post after the user's last read post
                // i.e., their next new post to read
                elseif ($user->last_read_post_number === $this->lastPostNumber - 1) {
                    $firstUnreadUsers[] = $user;
                }
            }

            $userIdsToNotify = Arr::pluck(array_merge($allUnreadUsers, $firstUnreadUsers), 'id');
            $usersToNotify = array_merge($usersToNotify, User::query()->whereIn('id', $userIdsToNotify)->get()->all());
        });

        $notifications->sync(
            new NewPostBlueprint($post),
            $usersToNotify
        );
    }
}
