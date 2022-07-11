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
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Subscriptions\Notification\NewPostBlueprint;
use Flarum\User\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

class SendReplyNotification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @var Post
     */
    protected $post;

    /**
     * @var int
     */
    protected $lastPostNumber;

    /**
     * @param Post $post
     * @param int|null $lastPostNumber
     */
    public function __construct(Post $post, $lastPostNumber)
    {
        $this->post = $post;
        $this->lastPostNumber = $lastPostNumber;
    }

    public function handle(NotificationSyncer $notifications)
    {
        /**
         * @var SettingsRepositoryInterface
         */
        $settings = resolve(SettingsRepositoryInterface::class);
        $post = $this->post;
        $discussion = $post->discussion;
        $defaultNotifyCriteria = $settings->get('flarum-subscriptions.notification_criteria');

        $basicUsersData = $discussion->readers()
            ->select('users.id', 'users.preferences', 'discussion_user.last_read_post_number')
            ->where('users.id', '!=', $post->user_id)
            ->where('discussion_user.subscription', 'follow')
            ->get()->all();

        $allUnreadUsers = [];
        $firstUnreadUsers = [];

        $forced_criteria = $settings->get('flarum-subscriptions.enforce_notification_criteria');

        /**
         * @var \Flarum\User\User $user
         */
        foreach ($basicUsersData as $user) {
            if ($forced_criteria) {
                $criteria = $defaultNotifyCriteria;
            } else {
                $criteria = $user->getPreference('flarum-subscriptions.user_notification_criteria', $defaultNotifyCriteria);
            }

            if ($criteria === 'first_new') {
                $firstUnreadUsers[] = $user;
            } else {
                $allUnreadUsers[] = $user;
            }
        }

        $firstUnreadUsers = array_filter($firstUnreadUsers, function ($user) {
            // Only notify if this is the next post after the user's last read post
            // i.e., their next new post to read
            return $user->last_read_post_number === $this->lastPostNumber - 1;
        });

        $userIdsToNotify = Arr::pluck(array_merge($allUnreadUsers, $firstUnreadUsers), 'id');
        $usersToNotify = User::query()->whereIn('id', $userIdsToNotify)->get()->all();

        $notifications->sync(
            new NewPostBlueprint($post),
            $usersToNotify
        );
    }
}
