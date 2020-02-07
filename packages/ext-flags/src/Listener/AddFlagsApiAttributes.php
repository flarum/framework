<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Listener;

use Flarum\Api\Event\Serializing;
use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Flags\Flag;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;

class AddFlagsApiAttributes
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function handle(Serializing $event)
    {
        if ($event->isSerializer(ForumSerializer::class)) {
            $event->attributes['canViewFlags'] = $event->actor->hasPermissionLike('discussion.viewFlags');

            if ($event->attributes['canViewFlags']) {
                $event->attributes['flagCount'] = (int) $this->getFlagCount($event->actor);
            }

            $event->attributes['guidelinesUrl'] = $this->settings->get('flarum-flags.guidelines_url');
        }

        if ($event->isSerializer(CurrentUserSerializer::class)) {
            $event->attributes['newFlagCount'] = (int) $this->getNewFlagCount($event->model);
        }

        if ($event->isSerializer(PostSerializer::class)) {
            $event->attributes['canFlag'] = $event->actor->can('flag', $event->model) && $this->checkFlagOwnPostSetting($event->actor, $event->model);
        }
    }

    /**
     * @param User $actor
     * @return int
     */
    protected function getFlagCount(User $actor)
    {
        return Flag::whereVisibleTo($actor)->distinct()->count('flags.post_id');
    }

    /**
     * @param User $actor
     * @return int
     */
    protected function getNewFlagCount(User $actor)
    {
        $query = Flag::whereVisibleTo($actor);

        if ($time = $actor->read_flags_at) {
            $query->where('flags.created_at', '>', $time);
        }

        return $query->distinct()->count('flags.post_id');
    }

    protected function checkFlagOwnPostSetting(User $actor, Post $post): bool
    {
        if ($actor->id === $post->user_id) {
            // If $actor is the post author, check to see if the setting is enabled
            return (bool) $this->settings->get('flarum-flags.can_flag_own');
        }
        // $actor is not the post author
        return true;
    }
}
